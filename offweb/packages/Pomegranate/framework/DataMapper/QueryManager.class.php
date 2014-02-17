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
 * The class to read and load queries from XML files.
 *
 * This is a singleton class to read and load queries and
 * other SQL statements from XML file. The read statements
 * are returned as QueryInfo objects.
 *
 * It expects the XML files to reside in $_QUERY_XML_PATH
 * folder, named as $class_name.query.xml.
 *
 * @author Mehran Ziadloo
 * @author Siamak Sobhany
 */
class QueryManager
{
    /**
     * The private static member to keep the lonely instance
     * of the class.
     * @var QueryManager
     */
    private static $instance = null;

    /**
     * A buffer of loaded DOMDocument(s) so if the same XML
     * file is requested, its DOMDocument will be available.
     * @var array
     */
    private static $xmlDocuments = array();

    /**
     * A buffer of loaded DOMXPath(s) so if the same XML file
     * is requested, its DOMXPath will be available.
     * @var array
     */
    private static $xmlXPaths = array();

    /**
     * The singleton static method to instantiate the class's
     * only object, or to return it if it is already instantiated.
     * @return QueryManager
     */
    public static function singleton()
    {
        if (self::$instance == null) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     * The method to load an SQL statement by stating the class
     * name and query name.
     * @param string $className
     * @param string $queryName
     * @throws Exception
     * @return QueryInfo
     */
    public function getQuery($queryFilePath, $queryName)
    {
        if (!isset(self::$xmlDocuments[$queryFilePath])) {
            self::$xmlDocuments[$queryFilePath] = new \DOMDocument();
            self::$xmlDocuments[$queryFilePath]->load($queryFilePath);
            self::$xmlXPaths[$queryFilePath] = new \DOMXPath(self::$xmlDocuments[$queryFilePath]);
        }
        $queryTags = self::$xmlXPaths[$queryFilePath]->query('/DataMapper/Query[@name="'.$queryName.'"]');
        if ($queryTags->length == 0)
            throw new framework\Exception('/DataMapper/Query[@name="'.$queryName.'"] is zero!');

        if ($queryTags->length > 1)
            throw new framework\Exception('/DataMapper/Query[@name="'.$queryName.'"] is more than one!');
            
        $info = $this->getQueryByXMLObject($queryTags->item(0));
        $info->fileName = $queryFilePath;
        $info->name = $queryName;
        
        $classes = self::$xmlXPaths[$queryFilePath]->query('/DataMapper/DomainModel/Class');
        for ($i=0; $i<$classes->length; $i++) {
            $info->tableMapper[$classes->item($i)->getAttribute('table')] = $classes->item($i)->getAttribute('class');
        }

        return $info;
    }

    /**
     * This method does the same as method getQuery, except that
     * it works on an XML loaded DOMElement. In fact getQuery
     * calls this method after loading a DOMElement objects.
     * @param DOMElement $element
     * @return QueryInfo
     */
    public function getQueryByXMLObject(\DOMElement $element)
    {
        $query = new QueryInfo();
        $SQLTags = $element->getElementsByTagName('SQL');
        $SQL = $SQLTags->item(0)->nodeValue;
        //eval("\$SQL = \"$SQL\";");
        $query->SQL = $SQL;
        $groups = $element->getElementsByTagName('ColumnGroup');
        if ($groups->length > 0) {
            $lastColumnIndex = 0;
            for ($i=0; $i<$groups->length; $i++) {
                $query->columnGroups[$i] = new ColumnGroup();
                $query->columnGroups[$i]->container = explode('/', $groups->item($i)->getAttribute('container'));
                if (count($query->columnGroups[$i]->container) == 1 && $query->columnGroups[$i]->container[0] == "")
                    $query->columnGroups[$i]->container = array();

                if ($groups->item($i)->hasAttribute('tables'))
                    $query->columnGroups[$i]->numberOfTables = $groups->item($i)->getAttribute('tables');
                if ($groups->item($i)->hasAttribute('keytable'))
                    $query->columnGroups[$i]->keyTableIndex = $groups->item($i)->getAttribute('keytable');
                if ($groups->item($i)->hasAttribute('size'))
                    $query->columnGroups[$i]->size = $groups->item($i)->getAttribute('size');
                if ($groups->item($i)->hasAttribute('class'))
                    $query->columnGroups[$i]->className = $groups->item($i)->getAttribute('class');
                if ($groups->item($i)->hasAttribute('instantiation_method'))
                    $query->columnGroups[$i]->instantiationMethod = $groups->item($i)->getAttribute('instantiation_method');
            }
        }

        return $query;
    }

}
