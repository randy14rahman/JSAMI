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

class DualConfig extends \Zend_Config
{
	protected $slaveFilePath;
	protected $slaveConfig;
	protected $sectionName;
	protected $configClassName;
	protected $writerClassName;
	protected $referenceConfig;

	public function __construct($masterFilePath, $slaveFileExtension, $sectionName, $configClassName, $writerClassName)
	{
		parent::__construct(array(), true);

		$pathInfo = pathinfo($masterFilePath);
		$this->slaveFilePath = $pathInfo['dirname'] .DIRECTORY_SEPARATOR. $pathInfo['filename'] . $slaveFileExtension . '.' . $pathInfo['extension'];
		$this->sectionName = $sectionName;
		$this->configClassName = $configClassName;
		$this->writerClassName = $writerClassName;

		$masterConfig = new $configClassName($masterFilePath, null, array('skipExtends' => true, 'skip_extends' => true));
		if (file_exists($this->slaveFilePath))
			$this->slaveConfig = new $configClassName($this->slaveFilePath, null, array('allowModifications' => true, 'allow_modifications' => true, 'skipExtends' => true, 'skip_extends' => true));
		else
			$this->slaveConfig = new \Zend_Config(array(), true);
		$hierarchy = $masterConfig->getExtends();
		foreach ($hierarchy as $parent => $child) {
			if (!isset($this->slaveConfig->$parent))
				$this->slaveConfig->$parent = array();
			if (!isset($this->slaveConfig->$child))
				$this->slaveConfig->$child = array();
		}

		$this->_setData($masterConfig, $hierarchy, $sectionName);
		$this->referenceConfig = clone $this;
	}

	protected function _setData(\Zend_Config $masterConfig, array $hierarchy, $sectionName)
	{
		if (isset($hierarchy[$sectionName]))
			$this->_setData($masterConfig, $hierarchy, $hierarchy[$sectionName]);

		if ($masterConfig->$sectionName != null) {
			$this->merge($masterConfig->$sectionName);
		}
		if ($this->slaveConfig->$sectionName != null) {
			$this->merge($this->slaveConfig->$sectionName);
		}
	}

	public function save()
	{
		$sectionName = $this->sectionName;
		if ($this->slaveConfig->$sectionName != null) {
			$this->_syncConfigs($this->referenceConfig, $this, $this->slaveConfig->$sectionName);
		}
		$className = $this->writerClassName;
		$writer = new $className();
		$writer->write($this->slaveFilePath, $this->slaveConfig);
	}

	protected function _syncConfigs(\Zend_Config $reference, \Zend_Config $workingCopy, \Zend_Config $difference)
	{
		foreach ($workingCopy as $name => $value) {
			if ($value instanceof \Zend_Config) {
				if (!isset($difference->$name))
					$difference->$name = array();
				if (!isset($reference->$name))
					$reference->$name = array();
				$this->_syncConfigs($reference->$name, $workingCopy->$name, $difference->$name);
			}
			else if ($reference->$name != $value)
				$difference->$name = $value;
		}
	}
}
