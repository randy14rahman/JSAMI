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
	public $columnGroups = array();	//of type ColumnGroup

	/**
	 * Maps database table names to domain model class names.
	 * ColumnGroup tags can override this mapping by specifying
	 * class for a group of fields.
	 * @var string
	 */
	public $tableMapper = array();
}
