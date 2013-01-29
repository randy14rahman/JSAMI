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

namespace Pomegranate\framework\JsClass;

class Response extends \Pomegranate\framework\Json\Response
{
	public function sendHeaders()
	{
		if (headers_sent()) {
			return;
		}

		if ($this->isError()) {
			switch ($this->isError()->getCode()) {
				case RpcServer\Error::ERROR_FILE_NOT_FOUND:
					header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
					break;

				case RpcServer\Error::ERROR_PERMISSION_DENIED:
					header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
					break;

				default:
					header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
					break;
			}
		}

		if (!$this->isError()
		&& ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
		|| $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
		|| $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile
		|| $this->result instanceof \Pomegranate\framework\Service\Result\LocalFile)) {
			$data = $this->result->getData();
			if ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
			|| $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
			|| $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile) {
				if (isset($data['filename']) && !empty($data['filename'])) {
					header("Content-Disposition: inline; filename=\"{$data['filename']}\"");
				}
				if (isset($data['etag']) && !empty($data['etag'])) {
					header("ETag: \"{$data['etag']}\"");
				}
				if (isset($data['last_modified']) && !empty($data['last_modified'])) {
					header("Last-Modified: {$data['last_modified']}");
				}
			}
			else if ($this->result instanceof \Pomegranate\framework\Service\Result\LocalFile) {
			}
		}
		else if (!$this->isError()) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 204 No Content');
			return;
		}

		header('Content-Type: application/javascript', true);
	}

	public function sendContent()
	{
		if (!$this->isError()
		&& ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
		|| $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
		|| $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile
		|| $this->result instanceof \Pomegranate\framework\Service\Result\LocalFile)) {
			$data = $this->result->getData();
			if ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
			|| $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
			|| $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile) {
				echo $data['content'];
			}
			else if ($this->result instanceof \Pomegranate\framework\Service\Result\LocalFile) {
				readfile($data['file_path']);
			}
		}
	}
}
