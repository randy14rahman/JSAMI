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

class Error
{
    static protected $_systemErrorsStrings = array(
        E_ERROR => 'E_ERROR'
        , E_WARNING => 'E_WARNING'
        , E_PARSE => 'E_PARSE'
        , E_NOTICE => 'E_NOTICE'
        , E_CORE_ERROR => 'E_CORE_ERROR'
        , E_CORE_WARNING => 'E_CORE_WARNING'
        , E_COMPILE_ERROR => 'E_COMPILE_ERROR'
        , E_COMPILE_WARNING => 'E_COMPILE_WARNING'
        , E_USER_ERROR => 'E_USER_ERROR'
        , E_USER_WARNING => 'E_USER_WARNING'
        , E_USER_NOTICE => 'E_USER_NOTICE'
        , E_STRICT => 'E_STRICT'
        , E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR'
        , E_DEPRECATED => 'E_DEPRECATED'
        , E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        , self::ERROR_PARSE => 'PARSE'
        , self::ERROR_INVALID_REQUEST => 'INVALID_REQUEST'
        , self::ERROR_INVALID_METHOD => 'INVALID_METHOD'
        , self::ERROR_INVALID_PARAMS => 'INVALID_PARAMS'
        , self::ERROR_INTERNAL => 'INTERNAL'
        , self::ERROR_INVALID_CLASS => 'INVALID_CLASS'
        , self::ERROR_OTHER => 'OTHER'
        , self::ERROR_FILE_NOT_FOUND => 'FILE_NOT_FOUND'
        , self::ERROR_PERMISSION_DENIED => 'PERMISSION_DENIED'
        , self::ERROR_TOO_BIG_REQUEST => 'TOO_BIG_REQUEST'
        , self::ERROR_EXECUTION_TIME_OVER => 'EXECUTION_TIME_OVER'
        , self::ERROR_NOT_ENOUGH_MEMORY => 'NOT_ENOUGH_MEMORY'
    );

    const ERROR_PARSE           = -32768;
    const ERROR_INVALID_REQUEST = -32600;
    const ERROR_INVALID_METHOD  = -32601;
    const ERROR_INVALID_PARAMS  = -32602;
    const ERROR_INTERNAL        = -32603;
    const ERROR_INVALID_CLASS   = -32604;
    const ERROR_OTHER           = -32000;
    const ERROR_FILE_NOT_FOUND  = -404;
    const ERROR_PERMISSION_DENIED = -403;
    const ERROR_TOO_BIG_REQUEST = -6400;
    const ERROR_EXECUTION_TIME_OVER = -6401;
    const ERROR_NOT_ENOUGH_MEMORY = -6402;

    /**
     * Current code
     * @var int
     */
    protected $_code = self::ERROR_OTHER;

    /**
     * Error data
     * @var mixed
     */
    protected $_data;

    /**
     * Error message
     * @var string
     */
    protected $_message;

    /**
     * Constructor
     *
     * @param  string $message
     * @param  int $code
     * @param  mixed $data
     * @return void
     */
    public function __construct($message = null, $code = self::ERROR_OTHER, $data = null)
    {
        $this->setMessage($message)
            ->setCode($code)
            ->setData($data);
    }

    /**
     * Set error code
     *
     * @param  int $code
     * @return Zend_Json_Server_Error
     */
    public function setCode($code)
    {
        if (!is_scalar($code)) {
            return $this;
        }

        $this->_code = (int) $code;

        return $this;
    }

    /**
     * Get error code
     *
     * @return int|null
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Set error message
     *
     * @param  string $message
     * @return Zend_Json_Server_Error
     */
    public function setMessage($message)
    {
        if (!is_scalar($message)) {
            return $this;
        }

        $this->_message = (string) $message;
        return $this;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * Set error data
     *
     * @param  mixed $data
     * @return Zend_Json_Server_Error
     */
    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * Get error data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Cast error to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'code'    => $this->getCodeString()
            , 'message' => $this->getMessage()
            , 'data'    => $this->getData()
        );
    }

    public function getCodeString()
    {
        if (isset(self::$_systemErrorsStrings[$this->_code])) {
            return self::$_systemErrorsStrings[$this->_code];
        }

        if (!empty($this->_code)) {
            return 'E_UNKOWN (' . $this->_code . ')';
        }

        return 'E_NONE';
    }
}
