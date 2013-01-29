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

//Zend auto class loader
require_once 'Zend/Loader/Autoloader.php';
$autoloader = \Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

//Config files and configurations
$configFile = getEnv('CONFIG_FILE');
$configSection = getEnv('APPLICATION_ENV');

$paths = array(get_include_path());
$paths[] = getEnv('PACKAGE_ROOT');
$paths = implode(PATH_SEPARATOR, $paths);
ini_set('include_path', $paths);

//Config
require_once getEnv('PACKAGE_ROOT').DIRECTORY_SEPARATOR.'Pomegranate'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'DualConfig.class.php';
require_once getEnv('PACKAGE_ROOT').DIRECTORY_SEPARATOR.'Pomegranate'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'DualConfig'.DIRECTORY_SEPARATOR.'Ini.class.php';
$config = new \Pomegranate\framework\DualConfig\Ini($configFile, '-local', $configSection);
Zend_Registry::set('config', $config);

//Include path
$paths = array(get_include_path());
if (!empty($config->offweb_path))
	$paths[] = $config->offweb_path;
$paths = implode(PATH_SEPARATOR, $paths);
ini_set('include_path', $paths);

//Pomegranate auto class loader
require_once 'Autoloader.class.php';
$_auto = new \Autoloader();
$autoloader->pushAutoloader($_auto);

//Timezone	
date_default_timezone_set($config->date_default_timezone);

//Loggin system
$logger = new \Zend_Log();
$writer = new \Zend_Log_Writer_Stream($config->log_file);
$logger->addWriter($writer);
$filter = new \Zend_Log_Filter_Priority((int)$config->log_threshold);
$logger->addFilter($filter);
\Zend_Registry::set('logger', $logger);
