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

namespace Pomegranate\framework\RpcServer;

class Request
{
    /**
     * Class Path
     * @var string
     */
    protected $_classPath = '';

    /**
     * Flag
     * @var bool
     */
    protected $_isMethodError = false;

    /**
     * Requested method
     * @var string
     */
    protected $_method;

    /**
     * Regex for method
     * @var string
     */
    protected $_methodRegex = '/^[a-z][a-z0-9_.]*$/i';

    protected $errors = array();
    
    /**
     * Set request method
     *
     * @param  string $name
     * @return Zend_Json_Server_Request
     */
    public function setMethod($name)
    {
        if (!preg_match($this->_methodRegex, $name)) {
            $this->_isMethodError = true;
        } else {
            $this->_method = $name;
        }
        return $this;
    }

    /**
     * Get request method name
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Is it a bad request?
     *
     * @return bool
     */
    public function isFault()
    {
        return count($this->errors) > 0;
    }

    /**
     * Indicate fault request
     *
     * @param  string $fault
     * @param  int $code
     * @param  mixed $data
     * @return \Pomegranate\framework\RpcServer\Error
     */
    public function fault($fault = null, $code = 404, $data = null)
    {
        $error = new \Pomegranate\framework\RpcServer\Error($fault, $code, $data);
        $this->errors[] = $error;
        return $error;
    }
    
    /**
     * Returns request's faults
     *
     * @return array
     */
    public function getFault()
    {
        return $this->errors;
    }
    
    public function getClass()
    {
        return $this->_classPath;
    }
}
