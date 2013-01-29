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

namespace Pomegranate\framework\Json;

use \Pomegranate\framework\RpcServer as RpcServer;

class Request extends \Pomegranate\framework\RpcServer\Request
{
    /**
     * Request ID
     * @var mixed
     */
    protected $_id;

    /**
     * Request parameters
     * @var array
     */
    protected $_params = array();

    /**
     * JSON-RPC version of request
     * @var string
     */
    protected $_version = '1.0';

	public function __construct($class_path, $raw_index)
	{
		$raw = empty($raw_index) ? file_get_contents('php://input') : (isset($_REQUEST[$raw_index]) ? $_REQUEST[$raw_index] : '');
		if (empty($raw)) {
			$this->fault('Empty request', RpcServer\Error::ERROR_INVALID_REQUEST);
			return;
		}
		$this->loadJson($raw);

		$tokens = explode('/', $class_path);
		foreach ($tokens as $key => &$token) {
			if (trim($token) == '') {
				unset($tokens[$key]);
			}
			else { 
				$token = trim($token);
			}
		}
		$tokens = array_values($tokens);
		$class_path = '\\' . implode('\\', $tokens);
		$this->_classPath = $class_path;

		$server = \Zend_Registry::get('server');
		$service = $this->_classPath.'::'.$this->_method;

		if (!$server->isAllowed($service)) {
			$this->_classPath = '\\Pomegranate\\framework\\Services';
			$this->_method = 'NotAuthorized';
			$this->addParam($service);
		}
	}

    /**
     * Set request state
     *
     * @param  array $options
     * @return Zend_Json_Server_Request
     */
    public function setOptions(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            } elseif ($key == 'jsonrpc') {
                $this->setVersion($value);
            }
        }
        return $this;
    }

    /**
     * Add a parameter to the request
     *
     * @param  mixed $value
     * @param  string $key
     * @return Zend_Json_Server_Request
     */
    public function addParam($value, $key = null)
    {
        if ((null === $key) || !is_string($key)) {
            $index = count($this->_params);
            $this->_params[$index] = $value;
        } else {
            $this->_params[$key] = $value;
        }

        return $this;
    }

    /**
     * Add many params
     *
     * @param  array $params
     * @return Zend_Json_Server_Request
     */
    public function addParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->addParam($value, $key);
        }
        return $this;
    }

    /**
     * Overwrite params
     *
     * @param  array $params
     * @return Zend_Json_Server_Request
     */
    public function setParams(array $params)
    {
        $this->_params = array();
        return $this->addParams($params);
    }

    /**
     * Retrieve param by index or key
     *
     * @param  int|string $index
     * @return mixed|null Null when not found
     */
    public function getParam($index)
    {
        if (array_key_exists($index, $this->_params)) {
            return $this->_params[$index];
        }

        return null;
    }

    /**
     * Retrieve parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Set request identifier
     *
     * @param  mixed $name
     * @return Zend_Json_Server_Request
     */
    public function setId($name)
    {
        $this->_id = (string) $name;
        return $this;
    }

    /**
     * Retrieve request identifier
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set JSON-RPC version
     *
     * @param  string $version
     * @return Zend_Json_Server_Request
     */
    public function setVersion($version)
    {
        if ('2.0' == $version) {
            $this->_version = '2.0';
        }
        else {
            $this->_version = '1.0';
        }
        return $this;
    }

    /**
     * Retrieve JSON-RPC version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Set request state based on JSON
     *
     * @param  string $json
     * @return void
     */
    public function loadJson($json)
    {
        require_once 'Zend/Json.php';
        try {
	        $options = \Zend_Json::decode($json);
	        $this->setOptions($options);
        }
        catch (\Exception $ex) {
        	$this->fault("Invalid request: '$json'", RpcServer\Error::ERROR_INVALID_REQUEST);
        }
    }

    /**
     * Cast request to JSON
     *
     * @return string
     */
    public function toJson()
    {
        $jsonArray = array(
            'method' => $this->getMethod()
        );
        if (null !== ($id = $this->getId())) {
            $jsonArray['id'] = $id;
        }
        $params = $this->getParams();
        if (!empty($params)) {
            $jsonArray['params'] = $params;
        }
        if ('2.0' == $this->getVersion()) {
            $jsonArray['jsonrpc'] = '2.0';
        }

        return \Zend_Json::encode($jsonArray);
    }

    /**
     * Cast request to string (JSON)
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
