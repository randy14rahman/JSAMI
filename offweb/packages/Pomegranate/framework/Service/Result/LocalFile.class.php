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

class LocalFile extends \Pomegranate\framework\Service\Result
{
    protected $file_path = '';
    protected $disposition;

    public function __construct($file_path, $disposition = 'inline')
    {
        $this->file_path = $file_path;
        $this->disposition = $disposition;
        if (!file_exists($file_path)) {
            //Early error generation
            file_get_contents($file_path);
        }
    }

    public function getData()
    {
        return array('file_path' => $this->file_path, 'disposition' => $this->disposition);
    }
}
