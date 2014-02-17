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

/**
 * A class to store a loaded SQL statement and its attributes.
 *
 * @author Mehran Ziadloo
 * @author Siamak Sobhany
 */
class QueryInfo
{
    const PARAM_BOOL = \PDO::PARAM_BOOL;
    const PARAM_NULL = \PDO::PARAM_NULL;
    const PARAM_INT = \PDO::PARAM_INT;
    const PARAM_STR = \PDO::PARAM_STR;
    const PARAM_LOB = \PDO::PARAM_LOB;
    const PARAM_STMT = \PDO::PARAM_STMT;
    const PARAM_INPUT_OUTPUT = \PDO::PARAM_INPUT_OUTPUT;

    /**
     * XML filename.
     * @var string
     */
    public $fileName = '';

    /**
     * The name for SQL statement, which is the same as
     * 'name' attribute for 'Query' tag in XML.
     * @var string
     */
    public $name = '';

    /**
     * The SQL statement which is the body part of the 'SQL' tag
     * in XML.
     * @var string
     */
    public $SQL = '';

    /**
     * An array of ColumnGroup(s) loaded from XML file. One QueryInfo
     * can have zero to arbitrary number of ColumnGroup(s).
     * @var array
     */
    public $columnGroups = array();    //of type ColumnGroup

    /**
     * Maps database table names to domain model class names.
     * ColumnGroup tags can override this mapping by specifying
     * class for a group of fields.
     * @var string
     */
    public $tableMapper = array();

    public $values = array();

    public $params = array();

    public function bindValue($parameter, $value, $data_type = self::PARAM_STR)
    {
        $this->values[$parameter] = array('value' => $value, 'data_type' => $data_type);
    }

    public function bindParam($parameter, &$variable, $data_type = self::PARAM_STR, $length = -1, $driver_options = false)
    {
        $this->params[$parameter] = array('variable' => &$variable, 'data_type' => $data_type, 'length' => $length, 'driver_options' => $driver_options);
    }
}
