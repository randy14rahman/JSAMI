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

namespace Pomegranate\framework\XmlRpc;

use \Pomegranate\framework\RpcServer as RpcServer;

class Request extends \Pomegranate\framework\RpcServer\Request
{
    /**
     * Request character encoding
     * @var string
     */
    protected $_encoding = 'UTF-8';

    /**
     * XML request
     * @var string
     */
    protected $_xml;

    /**
     * Method parameters
     * @var array
     */
    protected $_params = array();

    /**
     * XML-RPC type for each param
     * @var array
     */
    protected $_types = array();

    /**
     * XML-RPC request params
     * @var array
     */
    protected $_xmlRpcParams = array();

    /**
     * Create a new XML-RPC request
     *
     * @param string $method (optional)
     * @param array $params  (optional)
     */
    public function __construct($class_path, $raw_index)
    {
        $raw = empty($raw_index) ? file_get_contents('php://input') : (isset($_REQUEST[$raw_index]) ? $_REQUEST[$raw_index] : '');
        if (empty($raw)) {
            $this->fault('Empty request', RpcServer\Error::ERROR_INVALID_REQUEST);
            return;
        }
        $this->loadXml($raw);

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
     * Set encoding to use in request
     *
     * @param string $encoding
     * @return Zend_XmlRpc_Request
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        \Zend_XmlRpc_Value::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current request encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Add a parameter to the parameter stack
     *
     * Adds a parameter to the parameter stack, associating it with the type
     * $type if provided
     *
     * @param mixed $value
     * @param string $type Optional; type hinting
     * @return void
     */
    public function addParam($value, $type = null)
    {
        $this->_params[] = $value;
        if (null === $type) {
            // Detect type if not provided explicitly
            if ($value instanceof \Zend_XmlRpc_Value) {
                $type = $value->getType();
            }
            else {
                $xmlRpcValue = \Zend_XmlRpc_Value::getXmlRpcValue($value);
                $type = $xmlRpcValue->getType();
            }
        }
        $this->_types[]  = $type;
        $this->_xmlRpcParams[] = array('value' => $value, 'type' => $type);
    }

    /**
     * Set the parameters array
     *
     * If called with a single, array value, that array is used to set the
     * parameters stack. If called with multiple values or a single non-array
     * value, the arguments are used to set the parameters stack.
     *
     * Best is to call with array of the format, in order to allow type hinting
     * when creating the XMLRPC values for each parameter:
     * <code>
     * $array = array(
     *     array(
     *         'value' => $value,
     *         'type'  => $type
     *     )[, ... ]
     * );
     * </code>
     *
     * @access public
     * @return void
     */
    public function setParams()
    {
        $argc = func_num_args();
        $argv = func_get_args();
        if (0 == $argc) {
            return;
        }

        if ((1 == $argc) && is_array($argv[0])) {
            $params = array();
            $types = array();
            $wellFormed = true;
            foreach ($argv[0] as $arg) {
                if (!is_array($arg) || !isset($arg['value'])) {
                    $wellFormed = false;
                    break;
                }
                $params[] = $arg['value'];

                if (!isset($arg['type'])) {
                    $xmlRpcValue = \Zend_XmlRpc_Value::getXmlRpcValue($arg['value']);
                    $arg['type'] = $xmlRpcValue->getType();
                }
                $types[] = $arg['type'];
            }
            if ($wellFormed) {
                $this->_xmlRpcParams = $argv[0];
                $this->_params = $params;
                $this->_types = $types;
            }
            else {
                $this->_params = $argv[0];
                $this->_types = array();
                $xmlRpcParams = array();
                foreach ($argv[0] as $arg) {
                    if ($arg instanceof \Zend_XmlRpc_Value) {
                        $type = $arg->getType();
                    }
                    else {
                        $xmlRpcValue = \Zend_XmlRpc_Value::getXmlRpcValue($arg);
                        $type = $xmlRpcValue->getType();
                    }
                    $xmlRpcParams[] = array('value' => $arg, 'type' => $type);
                    $this->_types[] = $type;
                }
                $this->_xmlRpcParams = $xmlRpcParams;
            }
            return;
        }

        $this->_params = $argv;
        $this->_types = array();
        $xmlRpcParams = array();
        foreach ($argv as $arg) {
            if ($arg instanceof \Zend_XmlRpc_Value) {
                $type = $arg->getType();
            }
            else {
                $xmlRpcValue = \Zend_XmlRpc_Value::getXmlRpcValue($arg);
                $type = $xmlRpcValue->getType();
            }
            $xmlRpcParams[] = array('value' => $arg, 'type' => $type);
            $this->_types[] = $type;
        }
        $this->_xmlRpcParams = $xmlRpcParams;
    }

    /**
     * Retrieve the array of parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Return parameter types
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->_types;
    }

    /**
     * Load XML and parse into request components
     *
     * @param string $request
     * @return boolean True on success, false if an error occurred.
     */
    public function loadXml($request)
    {
        if (!is_string($request)) {
            $this->fault("Invalid request: '$request'", RpcServer\Error::ERROR_INVALID_REQUEST);
            return false;
        }

        // @see ZF-12293 - disable external entities for security purposes
        $loadEntities = libxml_disable_entity_loader(true);
        try {
            $dom = new \DOMDocument;
            $dom->loadXML($request);
            foreach ($dom->childNodes as $child) {
                if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    $this->fault('Invalid request: Invalid XML: Detected use of illegal DOCTYPE', RpcServer\Error::ERROR_INVALID_REQUEST);
                    return false;
                }
            }
            $xml = simplexml_import_dom($dom);
            libxml_disable_entity_loader($loadEntities);
        }
        catch (\Exception $e) {
            // Not valid XML
            libxml_disable_entity_loader($loadEntities);
            $this->fault('Invalid request: Invalid XML', RpcServer\Error::ERROR_INVALID_REQUEST);
            return false;
        }

        // Check for method name
        if (empty($xml->methodName)) {
            // Missing method name
            $this->fault("Invalid request: Missing method name: '{$xml->methodName}'", RpcServer\Error::ERROR_INVALID_REQUEST);
                return false;
        }

        $this->_method = (string) $xml->methodName;

        // Check for parameters
        if (!empty($xml->params)) {
            $types = array();
            $argv  = array();
            foreach ($xml->params->children() as $param) {
                if (!isset($param->value)) {
                    $this->fault("Invalid request: Missing parameter", RpcServer\Error::ERROR_INVALID_REQUEST);
                    return false;
                }

                try {
                    $param = \Zend_XmlRpc_Value::getXmlRpcValue($param->value, \Zend_XmlRpc_Value::XML_STRING);
                    $types[] = $param->getType();
                    $argv[] = $param->getValue();
                }
                catch (Exception $e) {
                    $this->fault("Invalid request: Invalid parameter", RpcServer\Error::ERROR_INVALID_REQUEST);
                    return false;
                }
            }

            $this->_types  = $types;
            $this->_params = $argv;
        }

        $this->_xml = $request;

        return true;
    }

    /**
     * Retrieve method parameters as XMLRPC values
     *
     * @return array
     */
    protected function _getXmlRpcParams()
    {
        $params = array();
        if (is_array($this->_xmlRpcParams)) {
            foreach ($this->_xmlRpcParams as $param) {
                $value = $param['value'];
                $type  = isset($param['type']) ? $param['type'] : \Zend_XmlRpc_Value::AUTO_DETECT_TYPE;

                if (!$value instanceof \Zend_XmlRpc_Value) {
                    $value = \Zend_XmlRpc_Value::getXmlRpcValue($value, $type);
                }
                $params[] = $value;
            }
        }

        return $params;
    }

    /**
     * Create XML request
     *
     * @return string
     */
    public function saveXml()
    {
        $args = $this->_getXmlRpcParams();
        $method = $this->getMethod();

        $generator = \Zend_XmlRpc_Value::getGenerator();
        $generator->openElement('methodCall')
            ->openElement('methodName', $method)
            ->closeElement('methodName');

        if (is_array($args) && count($args)) {
            $generator->openElement('params');

            foreach ($args as $arg) {
                $generator->openElement('param');
                $arg->generateXml();
                $generator->closeElement('param');
            }
            $generator->closeElement('params');
        }
        $generator->closeElement('methodCall');

        return $generator->flush();
    }

    /**
     * Return XML request
     *
     * @return string
     */
    public function __toString()
    {
        return $this->saveXML();
    }
}
