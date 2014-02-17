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

namespace Pomegranate\framework\DataMapper;

use \Pomegranate\framework as framework;

/**
 * A singleton class that is the parent for all Mapper classes.
 *
 * The Mapper class has the primary functionality to interact ith a database
 * and construct object graphs out of select queries.
 *
 * @author Mehran Ziadloo
 * @author Siamak Sobhany
 */
class Mapper
{
    const ERR_DUPLICATE_KEY = 1;
    
    /**
     * The private static member to keep the single instance
     * of the class.
     * @var Mapper
     */
    private static $instance = null;

    /**
     * True if a database transaction is being executed.
     * @var boolean
     */
    protected $isInsideTransaction = false;

    /**
     * A string to identify a database connection. This
     * string acts as the key to the dbConnection array.
     * @var string
     */
    protected $connectionIndex = '';

    /**
     * Each time the loadListByQuery method is called, this property is
     * populated with information about each laoded field (column) of the
     * requested query.
     * @var array
     */
    protected $fields = array();

    /**
     * An instance if Zend_Search_Lucene which is created and used by vhild mappers
     * but this class is in charge of commiting changes to it.
     * @var Zend_Search_Lucene
     */
    protected $searchEngine = null;

    /**
     * Number of rows in database that are affected by the
     * last executed sql. It is updated in executeQuery
     * method each time it is called.
     * @var number
     */
    private $numberOfAffectedRows = 0;

    /**
     * The list of keys, one for each databsae connection,
     * to find the owner of transaction. Each time a transaction
     * is started, a new key is added to this collection. And by
     * commiting or rolling back the transaction it will be
     * removed.
     * @var array
     */
    protected static $transactionKey = array();

    /**
     * The collection of connections to databases. They are not
     * necessarily of the same type (e.g. MySQL) as this class make use
     * of PDO connections which can connect to different types of
     * databases.
     * @var array
     */
    protected static $dbConnection = array();

    /**
     * The list of commited files.
     * @var array
     */
    protected static $committedFiles = array();

    /**
     * The only constructor of Mapper class. It is declared
     * protected so that the Mapper itself can not be instantiaed
     * directly.
     * @param string $dbIndex
     */
    protected function __construct($index = 'Default')
    {
        if ($index == 'Default') {
            $config = \Zend_Registry::get('config');
            $this->connectionIndex = $config->db_default_index;
        }
        else
            $this->connectionIndex = $index;
        $this->connectToDatabase();
    }

    /**
     * Class destructor.
     */
    public function __destruct()
    {
        if (isset(self::$transactionKey[$this->connectionIndex]) && $this->isInsideTransaction) {
            $this->isInsideTransaction = false;
            unset(self::$transactionKey[$this->connectionIndex]);
            if ($this->commitFiles()) {
                self::$dbConnection[$this->connectionIndex]->commit();
            }
            else {
                self::$dbConnection[$this->connectionIndex]->rollBack();
                $this->rollBackFiles();
            }
        }
    }

    /**
     * The singleton static method to instantiate the class's
     * only object, or to return it if it is already instantiated.
     * @return Mapper
     */
    public static function singleton()
    {
        if (self::$instance == null) {
            self::$instance = new Mapper();
        }
        return self::$instance;
    }


    /**
     * The method to start a transaction. It is safe to call this method
     * multiple times even if the previous transaction is neither commited
     * nor rolled back. This method returns a key which is required by
     * commit and rollback method to work. The second call forth to this
     * method will return false, meaning that the transaction is already
     * started. So when the matching commit/rollback call gets the false
     * key, it knows that it should not do anything.
     * @throws Exception
     * @return boolean|number
     */
    public function beginTransaction()
    {
        if (!isset(self::$transactionKey[$this->connectionIndex])) {
            $this->connectToDatabase();

            if (!isset(self::$dbConnection[$this->connectionIndex])) {
                throw new framework\Exception('Could not create a database connection for ' . $this->connectionIndex . ' database');
            }

            if (!self::$dbConnection[$this->connectionIndex]->beginTransaction())
                throw  new framework\Exception('Transaction could not be started.');

            $this->isInsideTransaction = true;
            self::$transactionKey[$this->connectionIndex] = microtime();
            return self::$transactionKey[$this->connectionIndex];
        }
        return false;
    }

    /**
     * Commits the transaction if started and if the given key is correct.
     * @param boolean|number $key
     * @return boolean
     */
    public function commit($key)
    {
        if (isset(self::$transactionKey[$this->connectionIndex]) && self::$transactionKey[$this->connectionIndex] === $key) {
            $this->isInsideTransaction = false;

            if ($this->commitFiles()) {
                unset(self::$transactionKey[$this->connectionIndex]);
                self::$dbConnection[$this->connectionIndex]->commit();

                if (!is_null($this->searchEngine)) {
                    $this->searchEngine->commit();
                }
                return true;
            }
            else
                return $this->rollBack($key);
        }
        return false;
    }

    /**
     * Rolls back the transaction if started and if the given key is correct.
     * @param boolean|number $key
     * @return boolean
     */
    public function rollBack($key)
    {
        if (isset(self::$transactionKey[$this->connectionIndex]) && self::$transactionKey[$this->connectionIndex] === $key) {
            $this->isInsideTransaction = false;

            unset(self::$transactionKey[$this->connectionIndex]);
            self::$dbConnection[$this->connectionIndex]->rollBack();

            $this->rollBackFiles();
            return true;
        }
        return false;
    }

    /**
     * Releases the list of commited files so that they can not be
     * rolled back later. This is due to the fact that, files are
     * commited once they are inserted. But their list is kept so
     * that they can be rolled back if needed.
     * @return boolean
     */
    public function commitFiles()
    {
        self::$committedFiles[$this->connectionIndex] = array();
        return true;
    }

    /**
     * Rolls back the list of commited files so that they go back
     * where they come from. This is due to the fact that, files
     * are commited once they are inserted. But their list is kept
     * so that they can be rolled back if needed.
     * @return boolean
     */
    public function rollBackFiles()
    {
        if (isset(self::$committedFiles[$this->connectionIndex]) && is_array(self::$committedFiles[$this->connectionIndex]))
            foreach(self::$committedFiles[$this->connectionIndex] as $file)
                $file->undo();
        self::$committedFiles[$this->connectionIndex] = array();
        return true;
    }

    /**
     * Executes an SQL statement in form of a QueryInfo object and
     * returns the number of affected rows. It throws an exception
     * in ase of errors.
     * @param QueryInfo $query
     * @throws Exception
     * @return string
     */
    public function executeQuery(QueryInfo $query)
    {
        $key = null;
        try {
            $key = self::beginTransaction();
            $stmt = self::$dbConnection[$this->connectionIndex]->prepare($query->SQL);
            foreach ($query->values as $parameter => $v) {
                $stmt->bindValue($parameter, $v['value'], $v['data_type']);
            }
            foreach ($query->params as $parameter => $p) {
                if ($p['driver_options'] !== false) {
                    $stmt->bindParam($parameter, $p['variable'], $p['data_type'], $p['length'], $p['driver_options']);
                }
                else if ($p['length'] !== -1) {
                    $stmt->bindParam($parameter, $p['variable'], $p['data_type'], $p['length']);
                }
                else {
                    $stmt->bindParam($parameter, $p['variable'], $p['data_type']);
                }
            }
            $stmt->execute();
            $this->numberOfAffectedRows = $stmt->rowCount();
            self::commit($key);
            return $this->numberOfAffectedRows;
        }

        catch (\Exception $e) {
            self::rollBack($key);
            throw new \Exception("SQL Error: {$query->name}@{$query->fileName}:\n" . $this->getError() . "\n\nSQL:" . $query->SQL, 0);
        }
    }

    /**
     * Executes an SQL statement in form of a QueryInfo object and
     * returns an array as the collection of selected rows. It
     * throws an exception in case of errors. It might return an
     * array of stdClass objects or instances of a custom class,
     * which is indicated in the QueryInfo parameter.
     * @param QueryInfo $query
     * @throws Exception
     * @return Ambigous <multitype:stdClass , multitype:>
     */
    public function loadListByQuery(QueryInfo $query)
    {
        $key = null;
        $result = null;
        $stmt = null;
        try {
            $key = self::beginTransaction();
            $stmt = self::$dbConnection[$this->connectionIndex]->prepare($query->SQL);
            foreach ($query->values as $parameter => $v) {
                $stmt->bindValue($parameter, $v['value'], $v['data_type']);
            }
            foreach ($query->params as $parameter => $p) {
                if ($p['driver_options'] !== false) {
                    $stmt->bindParam($parameter, $p['variable'], $p['data_type'], $p['length'], $p['driver_options']);
                }
                else if ($p['length'] !== -1) {
                    $stmt->bindParam($parameter, $p['variable'], $p['data_type'], $p['length']);
                }
                else {
                    $stmt->bindParam($parameter, $p['variable'], $p['data_type']);
                }
            }
            $stmt->execute();
        }
        catch (\Exception $e) {
            self::rollBack($key);
            throw new \Exception($e->getMessage() . "\nQuery was: " . $query->SQL, 0);
        }
        self::commit($key);

        $n = $stmt->columnCount();
        $this->fields = array();
        for ($i=0; $i<$n; $i++) {
            $this->fields[] = $stmt->getColumnMeta($i);
        }

        $list = array();
        $rows = array();

        $rows = $stmt->fetchAll(\PDO::FETCH_NUM);

        if (!is_array($query->columnGroups) || (is_array($query->columnGroups) && count($query->columnGroups) == 0)) {
            foreach ($rows as $i => $row) {
                $list[$i] = new \stdClass();
                foreach ($row as $f => $v)
                    $list[$i]->{$this->fields[$f]['name']} = $v;
            }
        }
        else {
            $columnNames = array();
            for($i=0; $i<count($this->fields); $i++) {
                $columnNames[$i] = $this->fields[$i]['name'];
            }

            self::fillExtraInfo($query, $this->fields);
            $list = self::toHierarchy($rows, $query->columnGroups, $columnNames);
        }

        return $list;
    }

    /**
     * Fills some extra information into the QueryInfo object retrieved
     * from the fields data of database.
     * @param QueryInfo $queryInfo
     * @param array $fields
     */
    private static function fillExtraInfo(QueryInfo $queryInfo, array $fields)
    {
        $indexCounter = 0;
        foreach ($queryInfo->columnGroups as $cg) {
            if ($cg->size === '-1' || $cg->size[0] === '+') {
                $cg->extra['from'] = $indexCounter;
                $tableCounter = 0;
                $lastTable = null;
                for ($i = $indexCounter; $i < count($fields); $i++) {
                    if ($lastTable != $fields[$i]['table']) {
                        $lastTable = $fields[$i]['table'];
                        $tableCounter++;
                        if ($cg->numberOfTables < $tableCounter) {
                            $cg->extra['to'] = $i - 1;
                            $indexCounter = $i;
                            break;
                        }
                    }
                }
                if ($i == count($fields)) {
                    $cg->extra['to'] = count($fields) - 1;
                    $indexCounter = count($fields);
                }

                if ($cg->size[0] === '+') {
                    $cg->extra['to'] += $cg->size;
                    $indexCounter += $cg->size;
                }
            }
            else {
                $cg->extra['from'] = $indexCounter;
                $cg->extra['to'] = $indexCounter + $cg->size - 1;
                $indexCounter += $cg->size;
            }
        }

        foreach ($queryInfo->columnGroups as $cg) {
            $tableCounter = 0;
            $lastTable = null;
            $startIndex = 0;
            for ($i = $cg->extra['from']; $i <= $cg->extra['to']; $i++) {
                if ($lastTable !== $fields[$i]['table']) {
                    $lastTable = $fields[$i]['table'];
                    if ($tableCounter == $cg->keyTableIndex) {
                        $startIndex = $i;
                        break;
                    }
                    $tableCounter++;
                }
            }
            for ($i = $startIndex; $i <= $cg->extra['to']; $i++) {
                if (isset($fields[$i]['flags']) && is_array($fields[$i]['flags']) && in_array('primary_key', $fields[$i]['flags'])) {
                    //The field is primary key
                    $cg->extra['index'] = $i;
                    break;
                }
            }
            if (!isset($cg->extra['index'])) {
                $cg->extra['index'] = $startIndex;
            }

            if ($cg->className === null) {
                if (isset($queryInfo->tableMapper[$lastTable])) {
                    $cg->className = $queryInfo->tableMapper[$lastTable];
                }
            }
        }
    }

    /**
     * Converts the tabular data retrieved from database into a
     * tree like hierarchy of objects (object graph) which is easier
     * to work with in PHP. The blue print of how this is done is embeded
     * in ColumnGroups of QueryInfo.
     * @param array $data
     * @param array $columnGroups
     * @param array $columnNames
     * @return array
     */
    private static function toHierarchy(array $data, array $columnGroups, array $columnNames)
    {
        $cgKeys = array_keys($columnGroups);

        foreach ($cgKeys as $cgKey) {
            $columnGroup =& $columnGroups[$cgKey];
            $columnGroup->extra['parent'] = self::getParentColumnGroup($columnGroups, $columnGroup);
            $columnGroup->extra['id_2_entity'] = array();
            $columnGroup->extra['parent_id_2_parent_entity'] = array();
            foreach ($data as $row) {
                $id = $row[$columnGroup->extra['index']];

                $parent_id = '';
                //Padding parent_id to the current id
                if ($id != null) {
                    $pcg = $columnGroup;
                    while ($pcg->extra['parent'] != null) {
                        $parent_id = $row[$pcg->extra['parent']->extra['index']] . '_' . $parent_id;
                        $pcg = $pcg->extra['parent'];
                    }
                    if ($parent_id != '') {
                        $id = $parent_id . $id;
                        $parent_id = substr($parent_id, 0, -1);
                    }
                }

                if ($id != null && !isset($columnGroup->extra['id_2_entity'][$id])) {
                    $information = new \stdClass();
                    for ($i=$columnGroup->extra['from']; $i<=$columnGroup->extra['to']; $i++)
                        $information->{$columnNames[$i]} = $row[$i];
                    $columnGroup->extra['id_2_entity'][$id] = $information;
                    unset($information);
                }

                if ($id != null && $parent_id != '') {
                    if (!isset($columnGroup->extra['parent_id_2_parent_entity'][$parent_id][$id])) {
                        $columnGroup->extra['parent_id_2_parent_entity'][$parent_id][$id] =& $columnGroup->extra['id_2_entity'][$id];
                    }
                }
            }
            
            unset($columnGroup);
        }

        foreach ($cgKeys as $cgKey) {
            $columnGroup =& $columnGroups[$cgKey];

            if ($columnGroup->extra['parent'] != null) {
                $collectionName = end($columnGroup->container);
                $parent =& $columnGroup->extra['parent']->extra['id_2_entity'];
                foreach ($columnGroup->extra['parent_id_2_parent_entity'] as $parentid => $children) {
                    $parent[$parentid]->$collectionName = array_values($children);
                }
                unset($parent);
            }

            unset($columnGroup);
        }

        foreach ($cgKeys as $cgKey) {
            if ($columnGroups[$cgKey]->extra['parent'] != null) {
                $count1 = count($columnGroups[$cgKey]->extra['parent']->extra['id_2_entity']);
                $cgKeys1 = array_keys($columnGroups[$cgKey]->extra['parent']->extra['id_2_entity']);
                $property_name = end($columnGroups[$cgKey]->container);
                for ($i=0; $i<$count1; $i++) {
                    $parent =& $columnGroups[$cgKey]->extra['parent']->extra['id_2_entity'][$cgKeys1[$i]];
                    if (!isset($parent->$property_name)) {
                        $parent->$property_name = array();
                    }
                    unset($parent);
                }
            }
        }

        foreach ($cgKeys as $cgKey)
            if ($columnGroups[$cgKey]->extra['parent'] == null)
                return array_values($columnGroups[$cgKey]->extra['id_2_entity']);
    }

    public static function makeTreeByCode(array $data, $codeColumn, $codeLen, $collectionName)
    {
        $entries = array();
        $pool = array();

        foreach ($data as &$d) {
            $pool[$d->$codeColumn] =& $d;
            if (strlen($d->$codeColumn) >= $codeLen)
                $d->{'parent_'.$codeColumn} = substr($d->$codeColumn, 0, -$codeLen);
            else if (strlen($d->$codeColumn))
                $d->{'parent_'.$codeColumn} = '';
            else
                $d->{'parent_'.$codeColumn} = null;
        }

        foreach ($data as &$d) {
            if ($d->{'parent_'.$codeColumn} === null) {
                $entries[] = $d;
                unset($d->{'parent_'.$codeColumn});
            }
            else if (!isset($pool[$d->{'parent_'.$codeColumn}])) {
                $entries[] = $d;
                unset($d->{'parent_'.$codeColumn});
            }
            else {
                $pool[$d->{'parent_'.$codeColumn}]->{$collectionName}[] = $d;
                unset($d->{'parent_'.$codeColumn});
            }
        }

        return $entries;
    }

    /**
     * Finds the parent ColumnGroup for the given one.
     * @param array $columnGroups
     * @param ColumnGroup $columnGroup
     * @return NULL|ColumnGroup
     */
    private static function &getParentColumnGroup(array &$columnGroups, ColumnGroup &$columnGroup)
    {
        $last = array_pop($columnGroup->container);
        if ($last != null) {
            $key = implode('/', $columnGroup->container);
            $columnGroup->container[] = $last;
        }
        else {
            $null = null;
            return $null;
        }

        foreach ($columnGroups as $cg)
            if (implode('/', $cg->container) == $key)
                return $cg;

        $null = null;
        return $null;
    }

    /**
     * Makes a PDO connection to the database using the $_DB_INFOS global
     * array and the current $this->dbIndex property. The connection is
     * stored in the static array property of self::$dbConnection and is identified
     * with $this->connectionIndex as the key.
     */
    protected function connectToDatabase()
    {
        $config = \Zend_Registry::get('config');
        
        $property = "db_{$this->connectionIndex}_dsn";
        $dsn = $config->$property;
        
        $property = "db_{$this->connectionIndex}_user";
        $username = $config->$property;
        
        $property = "db_{$this->connectionIndex}_pass";
        $password = $config->$property;

        if (!isset(self::$dbConnection[$this->connectionIndex])) {
            self::$dbConnection[$this->connectionIndex] = new \PDO($dsn, $username, $password);
            try {
                self::$dbConnection[$this->connectionIndex]->query('SET CHARACTER SET utf8');
                self::$dbConnection[$this->connectionIndex]->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
                self::$dbConnection[$this->connectionIndex]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
            catch (\Exception $ex) {
            }
        }
    }

    /**
     * Returns the auto-generated id for the last inserted record.
     * @return number
     */
    public function getLastInsertID()
    {
        return self::$dbConnection[$this->connectionIndex]->lastInsertId();
    }

    /**
     * Retruns the number of records selected by the last executed SELECT
     * statement, as if no LIMIT condition was specifiec for the query.
     * This is a MySQL specific extension to the SQL and other DBMSs might
     * not support it.
     * @return number
     */
    public function calcFoundRows()
    {
        $stmt = self::$dbConnection[$this->connectionIndex]->query('SELECT FOUND_ROWS() AS Count;');
        $rows = $stmt->fetchAll(\PDO::FETCH_NUM);
        return $rows[0][0];
    }

    /**
     * Returns the number of rows affected by the last executed SQL
     * statement.
     * @return number
     */
    public function affectedRows()
    {
        return $this->numberOfAffectedRows;
    }

    /**
     * Returns the error stored in PDO as the last error occured
     * for the connection.
     * @return array
     */
    public function getError()
    {
        self::$dbConnection[$this->connectionIndex]->errorInfo();
    }
}
