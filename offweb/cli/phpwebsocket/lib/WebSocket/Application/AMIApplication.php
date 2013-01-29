<?php

namespace WebSocket\Application;

require 'AMIClient.php';

class AMIApplication extends Application
{
	private $_clients = array();
	private $_clientAMIConnections = array();

	public function onConnect($client)
	{
		$id = $client->getClientId();
		$this->_clients[$id] = $client;
		$this->_clientAMIConnections[$id] = new AMICLient(array(
			'host' => 'localhost'
			, 'port' => '5038'
			, 'connect_timeout' => 5 //Connection timeout, in seconds.
			, 'read_timeout' => 5000 //R/W timeout, in milliseconds.
			, 'scheme' => '' //Connection scheme, like tcp:// or tls://
		));
		$this->_clientAMIConnections[$id]->open();
	}

	public function onDisconnect($client)
	{
		$id = $client->getClientId();
		unset($this->_clients[$id]);
		$this->_clientAMIConnections[$id]->close();
		unset($this->_clientAMIConnections[$id]);
	}

	public function onData($data, $client)
	{
		$id = $client->getClientId();
		$this->_clientAMIConnections[$id]->send($data);

	}

	public function run()
	{
		foreach ($this->_clientAMIConnections as $id => &$ami) {
			$messages = $ami->getMessages();
			foreach ($messages as $msg) {
				$this->_clients[$id]->send($msg);
			}
		}
	}
}
