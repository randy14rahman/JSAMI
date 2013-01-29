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

abstract class Response
{
	protected $errors = array();
	protected $result = null;

	public function setError(Error $error)
	{
		$this->errors[] = $error;
		return $this;
	}

	public function getError()
	{
		return $this->errors;
	}

	public function isError()
	{
		return count($this->errors) !== 0;
	}

	public function setResult(\Pomegranate\framework\Service\Result $result)
	{
		$this->result = $result;
	}

	public function getResult()
	{
		return $this->result;
	}

	abstract public function output();
}
