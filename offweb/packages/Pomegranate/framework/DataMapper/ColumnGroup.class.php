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
 * A class to store a ColumnGroup XML tag
 * for a QueryInfo object.
 *
 * @author Mehran Ziadloo
 * @author Siamak Sobhany
 */
class ColumnGroup
{
	/**
	 * Name of a porperty of the domain class up in the object
	 * hierarchy wich should hold objects created out of fields
	 * in this column group.
	 * @var string
	 */
	public $container = '';

	/**
	 * Number of database tables whose fields are grouped in this
	 * column groups. Is used for automatic group size determination.
	 * @var unknown_type
	 */
	public $numberOfTables = 1;

	/**
	 * The db table which holds the primary key of this column group
	 * @var unknown_type
	 */
	public $keyTableIndex = 0;

	/**
	 * Number of columns that this group is consisted of.
	 * @var string
	 */
	public $size = '-1';

	/**
	 * The name of class to instantiate objects for each
	 * row of data when retrieved from database. Set null
	 * so that stdClass is used instead.
	 * @var string | null
	 */
	public $className = null;

	/**
	 * Two instantiation methods are available: IdentityMap
	 * and NewOperator. Using the IdentityMap you can make sure
	 * that the same entity does not exist as two different
	 * objects. NewOperator makes a new object each time some
	 * entity is extracted from database.
	 * @var string
	 */
	public $instantiationMethod = 'IdentityMap';

	/**
	 * Some extra information that are used in Mapper class
	 * for intermediate work.
	 * @var array
	 */
	public $extra = array();
}
