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

namespace Pomegranate\framework\utilities;

class FileSystemAddress
{
    static public function ConcatAddresses()
    {
        $p = func_get_arg(0);
        for ($i=1; $i<func_num_args(); $i++)
            $p .= '/' . func_get_arg($i);
        $p = strtr($p, array('\\' => '/'));
        $result = preg_replace('/(\/\/+)/i', '/', $p);
        if (substr($result, 0, 5) == 'http:')
            $result = 'http:/' . substr($result, 5);
        return $result;
    }
}
