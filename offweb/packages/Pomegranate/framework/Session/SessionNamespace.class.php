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

namespace Pomegranate\framework\Session;

class SessionNamespace extends \Zend_Session_Namespace
{
    public function __construct($namespace = 'Default', $writable = true, $singleInstance = false)
    {
        \Pomegranate\framework\Session::start(true);
        parent::__construct($namespace, $singleInstance);
        \Pomegranate\framework\Session::loadNamespace($namespace, $writable);
    }

    public function isWritable()
    {
        return \Pomegranate\framework\Session::isNamespaceWritable($this->_namespace);
    }
}
