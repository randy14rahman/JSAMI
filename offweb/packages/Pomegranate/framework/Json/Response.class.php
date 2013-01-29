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

class Response extends \Pomegranate\framework\RpcServer\Response\Http
{
	/**
	 * Request ID
	 * @var mixed
	 */
	protected $_id;
	
	/**
	 * Service map
	 * @var Zend_Json_Server_Smd
	 */
	protected $_serviceMap;

	/**
	 * JSON-RPC version
	 * @var string
	 */
	protected $_version;

	/**
	 * Set request ID
	 *
	 * @param  mixed $name
	 * @return Zend_Json_Server_Response
	 */
	public function setId($name)
	{
		$this->_id = $name;
		return $this;
	}

	/**
	 * Get request ID
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
	 * @return Zend_Json_Server_Response
	 */
	public function setVersion($version)
	{
		$version = is_array($version) ? implode(' ', $version) : $version;
		if ((string)$version == '2.0') {
			$this->_version = '2.0';
		} else {
			$this->_version = null;
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
	 * Retrieve args
	 *
	 * @return mixed
	 */
	public function getArgs()
	{
		return $this->_args;
	}

	/**
	 * Set args
	 *
	 * @param mixed $args
	 * @return self
	 */
	public function setArgs($args)
	{
		$this->_args = $args;
		return $this;
	}

	/**
	 * Set service map object
	 *
	 * @param  Zend_Json_Server_Smd $serviceMap
	 * @return Zend_Json_Server_Response
	 */
	public function setServiceMap($serviceMap)
	{
		$this->_serviceMap = $serviceMap;
		return $this;
	}

	/**
	 * Retrieve service map
	 *
	 * @return Zend_Json_Server_Smd|null
	 */
	public function getServiceMap()
	{
		return $this->_serviceMap;
	}

	/**
	 * Cast to string (JSON)
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	public function sendHeaders()
	{
		if (headers_sent()) {
			return;
		}

		if ($this->isError()) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		}

		if (!$this->isError() && (null === $this->getId())) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 204 No Content');
			return;
		}

		header('Content-Type: application/json', true);
	}

	public function sendContent()
	{
		if ($this->isError()) {
			$errors = $this->getError();
			$result = array(
					'status' => array(
							'success' => false
							, 'message' => $errors[count($errors)-1]->getCodeString()
					)
					, 'data' => null
			);
			$arrayErrors = array();
			foreach ($errors as $e) {
				$arrayErrors[] = $e->toArray();
			}
			echo \Zend_Json::encode(array(
					'id' => $this->getId()
					, 'result' => $result
					, 'error' => $arrayErrors
					, 'jsonrpc' => $this->getVersion()
				)
			);
		}
		else if (null !== $this->getId() && $this->result instanceof \Pomegranate\framework\Service\Result\Model) {
			$result = array(
				'status' => $this->result->getStatus()
				, 'data' => $this->result->getData()
			);
			echo \Zend_Json::encode(array(
					'id' => $this->getId()
					, 'result' => $result
					, 'jsonrpc' => $this->getVersion()
				)
			);
		}
	}
}
