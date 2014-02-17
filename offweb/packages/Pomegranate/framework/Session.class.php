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

namespace Pomegranate\framework;

class Session extends \Zend_Session
{
    protected static $_writableNamespaces = array();

    /**
     * Constructor overriding - make sure that a developer cannot instantiate
     */
    protected function __construct()
    {
        parent::__construct();
    }

    public static function start($options = false)
    {
        if (parent::getSaveHandler() == null) {
            self::setNamespaceSaveHandler(new Session\SaveHandler\File());
        }
        parent::start($options);
    }

    public static function loadNamespace($namespace = 'Default', $writable = true)
    {
        if (isset(self::$_writableNamespaces[$namespace])) {
            return;
        }
        else if (parent::namespaceIsset($namespace) && $writable === false) {
            return;
        }
        else {
            if ($writable === true) {
                $toBeLoaded = array_keys(self::$_writableNamespaces);
                $toBeLoaded[] = $namespace;
                foreach (self::$_writableNamespaces as $namespace => $value) {
                    parent::getSaveHandler()->writeNamespace($namespace, serialize($_SESSION[$namespace]));
                    unset($_SESSION[$namespace]);
                }
                parent::getSaveHandler()->releaseLocks();
                self::$_writableNamespaces = array();
                sort($toBeLoaded);
                parent::getSaveHandler()->prepareToLock($toBeLoaded);
                foreach ($toBeLoaded as $namespace) {
                    $value = parent::getSaveHandler()->readNamespace($namespace, 'write');
                    if (!empty($value))
                        $_SESSION[$namespace] = unserialize($value);
                    else
                        $_SESSION[$namespace] = null;
                    self::$_writableNamespaces[$namespace] = true;
                }
            }
            else {
                $value = parent::getSaveHandler()->readNamespace($namespace, 'read');
                if (!empty($value))
                    $_SESSION[$namespace] = unserialize($value);
                else
                    $_SESSION[$namespace] = null;
            }
        }
    }
    
    public static function setNamespaceSaveHandler(Session\SaveHandler $saveHandler)
    {
        register_shutdown_function('session_write_close');
        parent::setSaveHandler($saveHandler);
    }

    public function isNamespaceWritable($namespace)
    {
        return isset(self::$_writableNamespaces[$namespace]);
    }

    public static function getWritableNamespaces()
    {
        return array_keys(self::$_writableNamespaces);
    }

    public static function _getWritableNamespacesData()
    {
        $result = array();
        foreach (self::$_writableNamespaces as $key => $value) {
            if (isset($_SESSION[$key])) {
                $result[$key] = serialize($_SESSION[$key]);
            }
            else {
                $result[$key] = serialize(array());
            }
        }
        self::$_writableNamespaces = array();
        return $result;
    }
}
