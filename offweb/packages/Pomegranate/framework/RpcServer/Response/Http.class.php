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

namespace Pomegranate\framework\RpcServer\Response;

abstract class Http extends \Pomegranate\framework\RpcServer\Response
{
    protected $headers = array();

    protected function get_mime_type($extension)
    {
        $config = \Zend_Registry::get('config');
        $mimePath = $config->mime_db_path;
        $regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($extension\s)/i";
        $lines = file($mimePath);
        foreach ($lines as $line) {
            if (substr($line, 0, 1) == '#')
                continue; // skip comments
            $line = rtrim($line) . " ";
            if (!preg_match($regex, $line, $matches))
                continue; // no match to the extension
            return $matches[1];
        }
        return false; // no match at all
    }

    public function output()
    {
        $this->sendHeaders();
        $this->sendContent();
    }

    abstract public function sendHeaders();
    abstract public function sendContent();
}
