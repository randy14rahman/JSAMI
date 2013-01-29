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

namespace Pomegranate\framework\Resource;

class Request extends \Pomegranate\framework\Json\Request
{
	public function __construct($path)
	{
		$path = \Pomegranate\framework\utilities\FileSystemAddress::ConcatAddresses('/', $path);
		if (strpos($path, '/_') !== false || strpos($path, '/.') !== false) {
			$json = "{ \"method\": \"NotAuthorized\", \"params\": [\"$path\"], \"id\": -1 }";
		}
		else {
			$config = \Zend_Registry::get('config');
			$file_path = \Pomegranate\framework\utilities\FileSystemAddress::ConcatAddresses($config->package_root, $path);
			$ext = pathinfo($file_path, PATHINFO_EXTENSION);
			$json = '';
			switch (strtolower($ext)) {
				case 'php':
				case 'js':
				case 'html':
				case 'htm':
					$json = "{ \"method\": \"Evaluate\", \"params\": [\"$file_path\"], \"id\": -1 }";
					break;

				default:
					$json = "{ \"method\": \"Read\", \"params\": [\"$file_path\"], \"id\": -1 }";
					break;
			}
		}

		$this->loadJson($json);
		$this->_classPath = '\\Pomegranate\\framework\\Services';
	}
}
