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

class EvaluateFile extends \Pomegranate\framework\Service\Result
{
    protected $file_path = '';
    protected $content = '';
    protected $disposition;

    public function __construct($file_path, $disposition = 'inline')
    {
        $this->file_path = $file_path;
        $this->disposition = $disposition;
        ob_start();
        require $this->file_path;
        $evaluation = ob_get_contents();
        ob_clean();

        $evaluation = strtr($evaluation, array("__PATH__" => '"'.$file_path.'"'));
        
        $config = \Zend_Registry::get('config');
        if (strpos($file_path, $config->package_root) === 0
        && substr($file_path, -9) == '.class.js') {
            $class_path = substr($file_path, strlen($config->package_root));
            $class_path = substr($class_path, 0, -9);
            $class_path = strtr($class_path, array(DIRECTORY_SEPARATOR => '.'));
            $evaluation = strtr($evaluation, array('__CLASS__' => '"'.$class_path.'"'));
        }
        
        $this->content = $evaluation;
    }

    public function getData()
    {
        $filename = pathinfo($this->file_path, PATHINFO_FILENAME);
        $extension = pathinfo($this->file_path, PATHINFO_EXTENSION);
        $etag = filemtime($this->file_path);
        $lastModified = date('D, j M Y H:i:s e', $etag);

        return array(
            'filename' => $filename . '.' . $extension
            , 'disposition' => $this->disposition
            , 'extension' => $extension
            , 'etag' => $etag
            , 'last_modified' => $lastModified
            , 'content' => $this->content
        );
    }
}
