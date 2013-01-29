<?php
namespace WebSocket\Application;

declare(ticks=1);

class AMIClient
{
	private $_host;
	private $_port;
	private $_cTimeout;
	private $_scheme;
	private $_rTimeout;
	private $_socket;
	private $_context;
	private $_currentProcessingMessage = '';

	public function __construct(array $options)
	{
		$this->_host = $options['host'];
		$this->_port = intval($options['port']);
		$this->_cTimeout = $options['connect_timeout'];
		$this->_rTimeout = $options['read_timeout'];
		$this->_scheme = isset($options['scheme']) ? $options['scheme'] : 'tcp://';
	}

	public function open()
	{
		$cString = $this->_scheme . $this->_host . ':' . $this->_port;
		$this->_context = stream_context_create();
		$errno = 0;
		$errstr = '';
		$this->_socket = @stream_socket_client($cString, $errno, $errstr, $this->_cTimeout, STREAM_CLIENT_CONNECT, $this->_context);
		if ($this->_socket === false) {
			throw new \Exception('Error connecting to ami: ' . $errstr);
		}
		@stream_set_blocking($this->_socket, 1);
		do {
			$id = @stream_get_line($this->_socket, 1024, "\r\n");
		} while (strlen(trim($id)) == 0 && !@feof($this->_socket));
		if (strstr($id, 'Asterisk') === false) {
			throw new \Exception('Unknown peer. Is this an ami?: ' . $id);
		}
		@stream_set_blocking($this->_socket, 0);
	}

	public function getMessages()
	{
		$msgs = array();
		$read = @fread($this->_socket, 65535);
		if ($read === false || @feof($this->_socket)) {
			throw new \Exception('Error reading');
		}
		$this->_currentProcessingMessage .= $read;
		while (($marker = strpos($this->_currentProcessingMessage, "\r\n\r\n"))) {
			$msg = substr($this->_currentProcessingMessage, 0, $marker);
			$this->_currentProcessingMessage = substr($this->_currentProcessingMessage, $marker + strlen("\r\n\r\n"));
			$msgs[] = $msg;
		}
		return $msgs;
	}

	public function send($messageToSend)
	{
		$length = strlen($messageToSend);
		if (@fwrite($this->_socket, $messageToSend) < $length) {
			throw new \Exception('Could not send message');
		}
	}

	public function close()
	{
		@stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR);
	}
}
