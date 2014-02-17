<?php
/**
 *   Copyright 2012 Mehran Ziadloo & Siamak Sobhany
 *   Pomegranate Framework Project
 *   (http://www.sourceforge.net/p/pome-framework)
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 **/

namespace Pomegranate\framework\Session\SaveHandler;

class File extends \Pomegranate\framework\Session\SaveHandler
{
    protected $_save_path = null;
    protected $_filePointer = array();

    /**
     * Open Session - retrieve resources
     *
     * @param string $save_path
     * @param string $session_id
     */
    public function open($save_path, $session_id)
    {
        if (strpos($save_path, ";") !== FALSE) {
            $save_path = substr($save_path, strpos ($save_path, ";")+1);
        }
        if (empty($save_path)) {
            $save_path = '/tmp';
        }
        else if (!is_dir($save_path)) {
            mkdir($save_path, 0775, true);
        }
        $this->_save_path = $save_path;
        $this->_session_id = $session_id;

        return true;
    }

    /**
     * Close Session - free resources
     *
     */
    public function close()
    {
        foreach ($this->_filePointer as $fp) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        $this->_filePointer = array();
        return true;
    }

    /**
     * Read session data
     *
     * @param string $session_id
     */
    public function read($session_id)
    {
        $this->_session_id = $session_id;
        
        $sess_pattern = "{$this->_save_path}/sess_{$this->_session_id}_*";
        $files = glob($sess_pattern);
        foreach ($files as $filename) {
            if (is_file($filename)) {
                touch($filename);
            }
        }
        
        return '';
    }

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $session_id
     * @param string $namespace
     */
    public function destroy($session_id)
    {
        foreach ($this->_filePointer as $namespace => $fp) {
            $this->destroyNamespace($namespace);
        }
    }

    /**
     * Garbage Collection - remove old session data older
     * than $maxlifetime (in seconds)
     *
     * @param int $maxlifetime
     */
    public function gc($maxlifetime)
    {
        $sess_pattern = "{$this->_save_path}/sess_{$this->_session_id}_*";
        $files = glob($sess_pattern);
        foreach ($files as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    /**
     * Read session data. $access_type can be one of the two
     * strings: read | write. Only namespaces loaded with write
     * access can later be written back to storage.
     *
     * @param string $session_id
     * @param string $namespace
     * @param string $access_type
     */
    public function readNamespace($namespace, $access_type)
    {
        $sess_file = "{$this->_save_path}/sess_{$this->_session_id}_{$namespace}";

        if (isset($this->_filePointer[$namespace])) {
            fseek($this->_filePointer[$namespace], 0, SEEK_SET);
            $stat = fstat($this->_filePointer[$namespace]);
            $sess_data = fread($this->_filePointer[$namespace], $stat['size']);
            return $sess_data;
        }
        if ($access_type == 'read') {
            if (is_file($sess_file)) {
                return file_get_contents($sess_file);
            }
            else {
                return '';
            }
        }
        else {
            //Write access
            if ($this->_filePointer[$namespace] = @fopen($sess_file, "a+")) {
                flock($this->_filePointer[$namespace], LOCK_EX);
                fseek($this->_filePointer[$namespace], 0, SEEK_SET);
                $stat = fstat($this->_filePointer[$namespace]);
                if ($stat['size'] > 0) {
                    $sess_data = fread($this->_filePointer[$namespace], $stat['size']);
                }
                else {
                    $sess_data = '';
                }
                return $sess_data;
            } else {
                return '';
            }
        }
    }

    /**
     * Write Session - commit data to resource
     *
     * @param string $session_id
     * @param mixed $data
     * @param string $namespace
     */
    public function writeNamespace($namespace, $data)
    {
        if (isset($this->_filePointer[$namespace])) {
            ftruncate($this->_filePointer[$namespace], 0);
            fseek($this->_filePointer[$namespace], 0, SEEK_SET);
            $x = fwrite($this->_filePointer[$namespace], $data);
            flock($this->_filePointer[$namespace], LOCK_UN);
            fclose($this->_filePointer[$namespace]);
            unset($this->_filePointer[$namespace]);
            return $x;
        }
        else {
            return false;
        }
    }

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $session_id
     * @param string $namespace
     */
    public function destroyNamespace($namespace)
    {
        if (isset($this->_filePointer[$namespace])) {
            $sess_file = "{$this->_save_path}/sess_{$this->_session_id}_{$namespace}";
            flock($this->_filePointer[$namespace], LOCK_UN);
            fclose($this->_filePointer[$namespace]);
            unset($this->_filePointer[$namespace]);
            return unlink($sess_file);
        }
        else {
            return false;
        }
    }
}
