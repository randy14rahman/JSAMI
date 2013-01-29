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

namespace Pomegranate\framework\Service\Result;

class PackJsClasses extends \Pomegranate\framework\Service\Result
{
	protected $content;

	public function __construct(array $class_paths)
	{
		$this->content = "Pome.suspendLoad();\n";
		$config = \Zend_Registry::get('config');
		foreach ($this->class_paths as $cls) {
			$file_path = $config->package_root . strtr($cls, array('.' => DIRECTORY_SEPARATOR)) . '.class.js';
			$f = file_get_contents($file_path);
			$f = strtr($f, array('__CLASS__' => '"'.$path.'"'));
			ob_start();
			eval('?>'.$f);
			$evaluation = ob_get_contents();
			ob_clean();
			$this->content .= $evaluation . "\n";
		}
		$this->content .= "Pome.resumeLoad();\n";
	}

	public function getData()
	{
		$etag = time();
		$filename = "PackJsClasses_$etag.js";
		$lastModified = date('D, j M Y H:i:s e', $etag);

		return array(
			'filename' => $filename
			, 'mime' => 'application/javascript'
			, 'etag' => $etag
			, 'last_modified' => $lastModified
			, 'content' => $this->content
		);
	}
}
