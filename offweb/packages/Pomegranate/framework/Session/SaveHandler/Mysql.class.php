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

/**
 * Database structure needed for this class to work:

CREATE TABLE IF NOT EXISTS `session` (
  `session_id` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `data` longtext NOT NULL,
  `expiration` datetime NOT NULL,
  PRIMARY KEY (`session_id`,`namespace`),
  KEY `expiration` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `session_lock` (
  `session_id` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  PRIMARY KEY (`session_id`,`namespace`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

Then

$handler = new \Pomegranate\framework\Session\SaveHandler\Mysql('mysql:host=localhost;dbname=Pomegranate', 'user', 'pass');
\Pomegranate\framework\Session::setNamespaceSaveHandler($handler);

\Pomegranate\framework\Session::start();
$defaultNamespace = new \Pomegranate\framework\Session\SessionNamespace('DefaultNS', false);

 */

namespace Pomegranate\framework\Session\SaveHandler;

class Mysql extends \Pomegranate\framework\Session\SaveHandler
{
    protected $_lockedNamespaces = array();
    protected $_dsn = '';
    protected $_user = '';
    protected $_pass = '';
    protected $_db = null;
    protected $_session_id = '';
    protected $_expiration = '';

    public function __construct($dsn, $user, $pass)
    {
        $this->_dsn = $dsn;
        $this->_user = $user;
        $this->_pass = $pass;
    }

    /**
     * Open Session - retrieve resources
     *
     * @param string $save_path
     * @param string $session_id
     */
    public function open($save_path, $session_id)
    {
        $this->_db = new \PDO($this->_dsn, $this->_user, $this->_pass);
        return true;
    }

    /**
     * Close Session - free resources
     *
     */
    public function close()
    {
        $this->releaseLocks();
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

        $max_life = ini_get('session.gc_maxlifetime');
        $this->_expiration = date('Y-m-d H:i:s', time() + $max_life);

        $sql = "UPDATE session SET expiration = :exp WHERE session_id = :sid";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindValue(':exp', $this->_expiration, \PDO::PARAM_STR);
        $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
        $stmt->execute();
        
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
        $sql = "DELETE FROM session_lock WHERE session_id = :sid";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
        $stmt->execute();

        $sql = "DELETE FROM session WHERE session_id = :sid";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Garbage Collection - remove old session data older
     * than $maxlifetime (in seconds)
     *
     * @param int $maxlifetime
     */
    public function gc($maxlifetime)
    {
        $sql = "DELETE FROM session_lock WHERE session_id IN (SELECT session_id FROM session WHERE session_id = :sid AND expiration < :now)";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
        $stmt->bindValue(':now', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $stmt->execute();

        $sql = "DELETE FROM session WHERE session_id = :sid AND expiration < :now";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
        $stmt->bindValue(':now', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $stmt->execute();
        
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
        if (isset($this->_lockedNamespaces[$namespace])) {
            return $this->_lockedNamespaces[$namespace];
        }
        if ($access_type == 'read') {
            $sql = "SELECT data FROM session WHERE session_id = :sid AND namespace = :ns";
            $stmt = $this->_db->prepare($sql);
            $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
            $stmt->bindValue(':ns', $namespace, \PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) == 1) {
                return $rows[0]['data'];
            }
            else {
                return '';
            }
        }
        else {
            //Write access
            $this->_lockedNamespaces[$namespace] = '';

            $sql = "SELECT session_id FROM session_lock WHERE session_id = :sid AND namespace = :ns FOR UPDATE";
            $stmt = $this->_db->prepare($sql);
            $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
            $stmt->bindValue(':ns', $namespace, \PDO::PARAM_STR);
            $stmt->execute();

            $sql = "SELECT data FROM session WHERE session_id = :sid AND namespace = :ns";
            $stmt = $this->_db->prepare($sql);
            $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
            $stmt->bindValue(':ns', $namespace, \PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) == 1) {
                $this->_lockedNamespaces[$namespace] = $rows[0]['data'];
            }

            return $this->_lockedNamespaces[$namespace];
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
        if (isset($this->_lockedNamespaces[$namespace])) {
            $sql = "UPDATE session SET data = :data WHERE session_id = :sid AND namespace = :ns";
            $stmt = $this->_db->prepare($sql);
            $stmt->bindValue(':data', $data, \PDO::PARAM_STR);
            $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
            $stmt->bindValue(':ns', $namespace, \PDO::PARAM_STR);
            $x = $stmt->execute();
            unset($this->_lockedNamespaces[$namespace]);
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
        if (isset($this->_lockedNamespaces[$namespace])) {
            $sql = "DELETE FROM session_lock WHERE session_id = :sid AND namespace = :ns";
            $stmt = $this->_db->prepare($sql);
            $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
            $stmt->bindValue(':ns', $namespace, \PDO::PARAM_STR);
            $stmt->execute();

            $sql = "DELETE FROM session WHERE session_id = :sid AND namespace = :ns";
            $stmt = $this->_db->prepare($sql);
            $stmt->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
            $stmt->bindValue(':ns', $namespace, \PDO::PARAM_STR);
            return $stmt->execute();
        }
        else {
            return false;
        }
    }

    public function prepareToLock(array $namespaces)
    {
        if ($this->_db->inTransaction()) {
            $this->_db->commit();
        }

        $this->_db->beginTransaction();

        $sql = "SELECT session_id FROM session_lock WHERE session_id = :sid AND namespace = :ns";
        $stmt0 = $this->_db->prepare($sql);
        $stmt0->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);

        $sql = "INSERT IGNORE INTO session_lock (session_id, namespace) VALUES (:sid, :ns)";
        $stmt1 = $this->_db->prepare($sql);
        $stmt1->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);

        $sql = "INSERT IGNORE INTO session (session_id, namespace, data, expiration) VALUES (:sid, :ns, '', :exp)";
        $stmt2 = $this->_db->prepare($sql);
        $stmt2->bindValue(':sid', $this->_session_id, \PDO::PARAM_STR);
        $stmt2->bindValue(':exp', $this->_expiration, \PDO::PARAM_STR);

        foreach ($namespaces as $ns) {
            $stmt0->bindValue(':ns', $ns, \PDO::PARAM_STR);
            $stmt0->execute();
            $rows = $stmt0->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) == 0) {
                $stmt1->bindValue(':ns', $ns, \PDO::PARAM_STR);
                $stmt1->execute();
                $stmt2->bindValue(':ns', $ns, \PDO::PARAM_STR);
                $stmt2->execute();
            }
        }

        $this->_db->commit();
        $this->_db->beginTransaction();
    }

    public function releaseLocks()
    {
        $this->_lockedNamespaces = array();
        if ($this->_db->inTransaction()) {
            $this->_db->commit();
        }
    }
}
