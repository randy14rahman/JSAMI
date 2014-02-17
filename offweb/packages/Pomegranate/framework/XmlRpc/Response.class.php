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

class Response extends \Pomegranate\framework\RpcServer\Response\Http
{
    /**
     * Return value
     * @var mixed
     */
    protected $_return;

    /**
     * Return type
     * @var string
     */
    protected $_type;

    /**
     * Response character encoding
     * @var string
     */
    protected $_encoding = 'UTF-8';

    /**
     * Fault, if response is a fault response
     * @var null|Zend_XmlRpc_Fault
     */
    protected $_fault = null;

    public function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        if ($this->isError()) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        }

        header('Content-Type: application/xml', true);
    }

    public function sendContent()
    {
        $result = null;
        if ($this->isError()) {
            $errors = $this->getError();
            $arrayErrors = array();
            foreach ($errors as $e) {
                $arrayErrors[] = $e->toArray();
            }
            $result = array(
                'status' => array(
                    'success' => false
                    , 'message' => $errors[0]->getCodeString()
                )
                , 'error' => $arrayErrors
            );
        }
        else if ($this->result instanceof \Pomegranate\framework\Service\Result\Model) {
            $result = array(
                'status' => $this->result->getStatus()
                , 'data' => $this->result->getData()
            );
        }
        
        if ($result != null) {
            $value = \Zend_XmlRpc_Value::getXmlRpcValue($result);
            $generator = \Zend_XmlRpc_Value::getGenerator();
            $generator->openElement('methodResponse')
                ->openElement('params')
                ->openElement('param');
            $value->generateXml();
            $generator->closeElement('param')
                ->closeElement('params')
                ->closeElement('methodResponse');
            
            echo $generator->flush();
        }
    }

    /**
     * Set encoding to use in response
     *
     * @param string $encoding
     * @return Zend_XmlRpc_Response
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        \Zend_XmlRpc_Value::setEncoding($encoding);
        return $this;
    }

    /**
     * Retrieve current response encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Set the return value
     *
     * Sets the return value, with optional type hinting if provided.
     *
     * @param mixed $value
     * @param string $type
     * @return void
     */
    public function setReturnValue($value, $type = null)
    {
        $this->_return = $value;
        $this->_type = (string) $type;
    }

    /**
     * Retrieve the return value
     *
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->_return;
    }

    /**
     * Retrieve the XMLRPC value for the return value
     *
     * @return Zend_XmlRpc_Value
     */
    protected function _getXmlRpcReturn()
    {
        return \Zend_XmlRpc_Value::getXmlRpcValue($this->_return);
    }

    /**
     * Load a response from an XML response
     *
     * Attempts to load a response from an XMLRPC response, autodetecting if it
     * is a fault response.
     *
     * @param string $response
     * @return boolean True if a valid XMLRPC response, false if a fault
     * response or invalid input
     */
    public function loadXml($response)
    {
        if (!is_string($response)) {
            $this->_fault = new \Zend_XmlRpc_Fault(650);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        // @see ZF-12293 - disable external entities for security purposes
        $loadEntities = libxml_disable_entity_loader(true);
        $useInternalXmlErrors = libxml_use_internal_errors(true);
        try {
            $dom = new \DOMDocument;
            $dom->loadXML($response);
            foreach ($dom->childNodes as $child) {
                if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    require_once 'Zend/XmlRpc/Exception.php';
                    throw new \Zend_XmlRpc_Exception('Invalid XML: Detected use of illegal DOCTYPE');
                }
            }
            // TODO: Locate why this passes tests but a simplexml import doesn't
            // $xml = simplexml_import_dom($dom);
            $xml = new \SimpleXMLElement($response);
            libxml_disable_entity_loader($loadEntities);
            libxml_use_internal_errors($useInternalXmlErrors);
        }
        catch (\Exception $e) {
            libxml_disable_entity_loader($loadEntities);
            libxml_use_internal_errors($useInternalXmlErrors);
            // Not valid XML
            $this->_fault = new \Zend_XmlRpc_Fault(651);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        if (!empty($xml->fault)) {
            // fault response
            $this->_fault = new \Zend_XmlRpc_Fault();
            $this->_fault->setEncoding($this->getEncoding());
            $this->_fault->loadXml($response);
            return false;
        }

        if (empty($xml->params)) {
            // Invalid response
            $this->_fault = new \Zend_XmlRpc_Fault(652);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        try {
            if (!isset($xml->params) || !isset($xml->params->param) || !isset($xml->params->param->value)) {
                require_once 'Zend/XmlRpc/Value/Exception.php';
                throw new \Zend_XmlRpc_Value_Exception('Missing XML-RPC value in XML');
            }
            $valueXml = $xml->params->param->value->asXML();
            $value = \Zend_XmlRpc_Value::getXmlRpcValue($valueXml, \Zend_XmlRpc_Value::XML_STRING);
        }
        catch (\Zend_XmlRpc_Value_Exception $e) {
            $this->_fault = new \Zend_XmlRpc_Fault(653);
            $this->_fault->setEncoding($this->getEncoding());
            return false;
        }

        $this->setReturnValue($value->getValue());
        return true;
    }

    /**
     * Return response as XML
     *
     * @return string
     */
    public function saveXml()
    {
        $value = $this->_getXmlRpcReturn();
        $generator = Zend_XmlRpc_Value::getGenerator();
        $generator->openElement('methodResponse')
            ->openElement('params')
            ->openElement('param');
        $value->generateXml();
        $generator->closeElement('param')
            ->closeElement('params')
            ->closeElement('methodResponse');

        return $generator->flush();
    }

    /**
     * Return XML response
     *
     * @return string
     */
    public function __toString()
    {
        return $this->saveXML();
    }
}
