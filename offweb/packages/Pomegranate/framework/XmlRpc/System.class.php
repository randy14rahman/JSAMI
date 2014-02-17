<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Server
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: System.php 24593 2012-01-05 20:35:02Z matthew $
 */

namespace Pomegranate\framework\XmlRpc;

use \Pomegranate\framework\Service\Result as Result;

/**
 * XML-RPC system.* methods
 *
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Server
 * @copyright  Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class System
{
    /**
    * @var Zend_XmlRpc_Server
    */
    protected $_server;

    /**
     * Constructor
     *
     * @param  Zend_XmlRpc_Server $server
     * @return void
     */
    public function __construct(Server $server)
    {
        $this->_server = $server;
    }

    /**
     * List all available XMLRPC methods
     *
     * Returns an array of methods.
     *
     * @return array
     */
    public function listMethods()
    {
        $table = $this->_server->getDispatchTable()->getMethods();
        return new Result\Model(true, '', array_keys($table));
    }

    /**
     * Display help message for an XMLRPC method
     *
     * @param string $method
     * @return string
     */
    public function methodHelp($method)
    {
        $table = $this->_server->getDispatchTable();
        if (!$table->hasMethod($method)) {
            $this->_server->fault('Method "' . $method . '" does not exist', RpcServer\Error::ERROR_INVALID_METHOD);
            return;
        }

        return new Result\Model(true, '', $table->getMethod($method)->getMethodHelp());
    }

    /**
     * Return a method signature
     *
     * @param string $method
     * @return array
     */
    public function methodSignature($method)
    {
        $table = $this->_server->getDispatchTable();
        if (!$table->hasMethod($method)) {
            $this->_server->fault('Method "' . $method . '" does not exist', RpcServer\Error::ERROR_INVALID_METHOD);
            return;
        }
        $method = $table->getMethod($method)->toArray();
        return new Result\Model(true, '', $method['prototypes']);
    }

    /**
     * Multicall - boxcar feature of XML-RPC for calling multiple methods
     * in a single request.
     *
     * Expects a an array of structs representing method calls, each element
     * having the keys:
     * - methodName
     * - params
     *
     * Returns an array of responses, one for each method called, with the value
     * returned by the method. If an error occurs for a given method, returns a
     * struct with a fault response.
     *
     * @see http://www.xmlrpc.com/discuss/msgReader$1208
     * @param  array $methods
     * @return void
     */
    public function multicall($methods)
    {
        $this->_server->fault('system.multicall is disabled in Pomegranate.', RpcServer\Error::ERROR_PERMISSION_DENIED);
        return;
    }
}
