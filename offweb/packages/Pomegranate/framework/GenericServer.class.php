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

namespace Pomegranate\framework;

class GenericServer implements RpcServer\RpcInterface
{
	/**
	 * Server holder
	 * 
	 * \Pomegranate\framework\RpcServer\Interface
	 */
	protected $server;
	protected $request;
	protected $response;

	protected $class_path;
	protected $request_type;
	protected $response_type;
	protected $raw_index;

	public function __construct($class_path, $request_type, $response_type, $raw_index)
	{
		ob_start();
		\Zend_Registry::set('server', $this);
		set_error_handler(array($this, 'error_handler'));
		register_shutdown_function(array($this, 'shutdown_function'));
		
		$this->class_path = $class_path;
		$this->request_type = $request_type;
		$this->response_type = $response_type;
		$this->raw_index = $raw_index;

		$this->createServer();
		$this->createRequest();
		$this->createResponse();

		$this->server->setRequest($this->request);
		$this->server->setResponse($this->response);
		$this->server->setClass($this->request->getClass());
		
		$this->setAutoEmitResponse(false);
	}

	/**
	 * Attach a function as a server method
	 *
	 * Namespacing is primarily for xmlrpc, but may be used with other
	 * implementations to prevent naming collisions.
	 *
	 * @param string $function
	 * @param string $namespace
	 * @param null|array Optional array of arguments to pass to callbacks at
	 * dispatch.
	 * @return void
	 */
	public function addFunction($function, $namespace = '')
	{
		try {
			return $this->server->addFunction($function, $namespace);
		}
		catch (\Exception $ex) {
			$this->fault($ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * Attach a class to a server
	 *
	 * The individual implementations should probably allow passing a variable
	 * number of arguments in, so that developers may define custom runtime
	 * arguments to pass to server methods.
	 *
	 * Namespacing is primarily for xmlrpc, but could be used for other
	 * implementations as well.
	 *
	 * @param mixed $class Class name or object instance to examine and attach
	 * to the server.
	 * @param string $namespace Optional namespace with which to prepend method
	 * names in the dispatch table.
	 * methods in the class will be valid callbacks.
	 * @param null|array Optional array of arguments to pass to callbacks at
	 * dispatch.
	 * @return void
	 */
	public function setClass($class, $namespace = '', $argv = null)
	{
		try {
			return $this->server->setClass($class, $namespace, $argv);
		}
		catch (\Exception $ex) {
			$this->fault($ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * Indicate fault response
	 *
	 * @param  string $fault
	 * @param  int $code
	 * @param  mixed $data
	 * @return \Pomegranate\framework\RpcServer\Error
	 */
	public function fault($fault = null, $code = 404, $data = null)
	{
		return $this->server->fault($fault, $code, $data);
	}

	/**
	 * Handle a request
	 *
	 * Requests may be passed in, or the server may automagically determine the
	 * request based on defaults. Dispatches server request to appropriate
	 * method and returns a response
	 *
	 * @param mixed \Pomegranate\framework\RpcServer\Request
	 * @return mixed
	 */
	public function handle($request = false)
	{
		try {
			return $this->server->handle($request);
		}
		catch (\Exception $ex) {
			$this->fault($ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * Return a server definition array
	 *
	 * Returns a server definition array as created using
	 * {@link * Zend_Server_Reflection}. Can be used for server introspection,
	 * documentation, or persistence.
	 *
	 * @access public
	 * @return array
	 */
	public function getFunctions()
	{
		return $this->server->getFunctions();
	}

	/**
	 * Load server definition
	 *
	 * Used for persistence; loads a construct as returned by {@link getFunctions()}.
	 *
	 * @param array $array
	 * @return void
	 */
	public function loadFunctions($definition)
	{
		try {
			return $this->server->loadFunctions($definition);
		}
		catch (\Exception $ex) {
			$this->fault($ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * Set server persistence
	 *
	 * @todo Determine how to implement this
	 * @param int $mode
	 * @return void
	 */
	public function setPersistence($mode)
	{
		return $this->server->setPersistence($mode);
	}

	protected function createServer()
	{
		switch (strtolower($this->request_type)) {
			case 'xml':
				$this->server = new XmlRpc\Server();
				break;

			default:
				$this->server = new Json\Server();
				break;
		}
	}

	protected function createRequest()
	{
		switch (strtolower($this->request_type)) {
			case 'xml':
				$this->request = new XmlRpc\Request($this->class_path, $this->raw_index);
				break;

			case 'url':
				$this->request = new Resource\Request($this->class_path);
				break;

			default:
				$this->request = new Json\Request($this->class_path, $this->raw_index);
				break;
		}
	}

	protected function createResponse()
	{
		switch (strtolower($this->response_type)) {
			case 'class':
				$this->response = new JsClass\Response();
				break;

			case 'model':
				if (strtolower($this->request_type) == 'xml') {
					$this->response = new XmlRpc\Response();
				}
				else {
					$this->response = new Json\Response();
					$this->response->setId($this->request->getId());
				}
				break;

			case 'resource':
				$this->response = new Resource\Response();
				break;
		}
	}

	/**
	 * Set flag indicating whether or not to auto-emit response
	 *
	 * @param  bool $flag
	 * @return \Pomegranate\framework\RpcServer
	 */
	public function setAutoEmitResponse($flag)
	{
		$this->server->setAutoEmitResponse($flag);
	}

	/**
	 * Will we auto-emit the response?
	 *
	 * @return bool
	 */
	public function autoEmitResponse()
	{
		return $this->server->autoEmitResponse();
	}
	
    /**
     * Retrieve the response
     *
     * @return RpcServer\Response
     */
	public function getResponse()
	{
		return $this->server->getResponse();
	}

	public function isAllowed($service)
	{
		return true;
	}

	public function error_handler($errno, $errstr, $errfile, $errline, array $errcontext)
	{
		if ($this->response != null)
			$this->response->setError(new RpcServer\Error($errstr, $errno, $errfile . ' [' . $errline . ']'));
		else
			echo "$errstr($errno)\n $errfile [$errline]";
		return false;
	}

	public function shutdown_function()
	{
		if ($e = error_get_last()) {
			$message = null;
			$type = 0;
			$prompt = null;
			if (strpos($e['message'], 'POST Content-Length') !== false) {
				$prompt = $message = 'Upload file size limitation exceeded.';
				$type = $e['type'];
			}
			else if (strpos($e['message'], 'Maximum execution time of') !== false) {
				$prompt = $message = $e['message'];
				$type = $e['type'];
			}
			else if (strpos($e['message'], 'Allowed memory size of ') !== false) {
				$prompt = $message = 'Allowed memory size exhausted.';
				$type = $e['type'];
			}
			else if ($e['type'] == E_ERROR
			|| $e['type'] == E_PARSE
			|| $e['type'] == E_CORE_ERROR
			|| $e['type'] == E_CORE_WARNING
			|| $e['type'] == E_COMPILE_ERROR
			|| $e['type'] == E_COMPILE_WARNING) {
				$message = $e['message'];
				$type = $e['type'];
				$prompt = 'Server error';
			}
			else if (!$this->getResponse()->isError()) {
				$message = $e['message'];
				$type = $e['type'];
				$prompt = 'Server error';
			}

			if ($message != null) {
				$this->getResponse()->setError(new RpcServer\Error($message, $type, $e['file'] . ' [' . $e['line'] . ']'));
			}
		}

		ob_clean();

		$this->getResponse()->output();
	}}
