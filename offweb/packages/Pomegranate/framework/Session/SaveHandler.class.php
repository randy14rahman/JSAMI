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

namespace Pomegranate\framework\Session;

abstract class SaveHandler implements \Zend_Session_SaveHandler_Interface
{
    protected $_session_id = null;

    /**
     * Open Session - retrieve resources
     *
     * @param string $save_path
     * @param string $session_id
     */
    public function open($save_path, $session_id)
    {
        $this->_session_id = $session_id;
        return true;
    }

    /**
     * Close Session - free resources
     *
     */
    public function close()
    {
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
        return '';
    }

    /**
     * Write Session - commit data to resource
     *
     * @param string $session_id
     * @param mixed $data
     */
    public function write($session_id, $data)
    {
        $result = true;
        $namespaces = \Pomegranate\framework\Session::_getWritableNamespacesData();
        foreach ($namespaces as $key => $data) {
            $result = $this->writeNamespace($key, $data) & $result;
        }
        $this->releaseLocks();
        return $result;
    }

    /**
     * Read session data. $access_type can be one of the two
     * strings: read | write. Only namespaces loaded with write
     * access can later be written back to storage.
     *
     * @param string $namespace
     * @param string $access_type
     */
    abstract public function readNamespace($namespace, $access_type);

    /**
     * Prior to read namespaces with write access, this
     * function is called with the list of all namespaces
     * that are going to be loaded.
     *
     * @param array $namespaces
     */
    public function prepareToLock(array $namespaces) {}

    /**
     * After writing back to storage all the write access
     * namespaces, this function is called since some
     * save handlers can not release locks individually
     */
    public function releaseLocks() {}

    /**
     * Write Session - commit data to resource
     *
     * @param string $session_id
     * @param mixed $data
     * @param string $namespace
     */
    abstract public function writeNamespace($namespace, $data);

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $session_id
     * @param string $namespace
     */
    abstract public function destroyNamespace($namespace);
}
