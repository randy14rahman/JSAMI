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

use \Pomegranate\framework\Service\Result as Result;

class Services
{
	public function RPCUnpack(array $packages)
	{
		try {
			$results = array();
			$server = \Zend_Registry::get('server');
			$index = 0;

			foreach ($packages as $p) {
				$p['namespace'] = isset($p['namespace']) ? strtr($p['namespace'], array('.' => '\\')) : '';
				$p['class_path'] = strtr($p['class_path'], array('.' => '\\'));
				$p['params'] = isset($p['params']) ? $p['params'] : array();

				$classPath = '\\' . $p['namespace'] . '\\' . $p['class_path'];
				$classPath = strtr($classPath, array('/' => '\\'));
				$classPath = preg_replace('/\\\\+/', '\\', $classPath);
				$id = $p['id'];
				$method = $p['method'];
				$params = $p['params'];

				$service = $classPath.'::'.$method;
				try {
					if ($server->isAllowed($service)) {
						$obj = new $classPath();
						$data = call_user_func_array(array($obj, $method), $params);
						
						if ($data instanceof \Pomegranate\framework\Service\Result\Model) {
							$result = array(
									'status' => $data->getStatus()
									, 'data' => $data->getData()
							);
						}
						else {
							$result = $data;
						}

						$results[$index] = array('id' => $id, 'error' => null, 'result' => $result);
					}
					else {
						$obj = new self();
						$data = call_user_func_array(array($obj, "NotAuthorized"), array($service));

						$results[$index] = array('id' => $id, 'error' => null, 'result' => $data);
					}
				}
				catch (Exception $ex) {
					$results[$index] = array('id' => $id, 'error' => array('code' => $ex->getCode(), 'message' => $ex->getMessage(), 'data' => array()), 'result' => null);
				}
				$index++;
			}
			return new Result\Model(true, '', $results);
		}
		catch (\Pomegranate\framework\Exception $ex) {
			return new Result\Model(false, $ex->getMessage());
		}
	}

	public function NotAuthorized($service)
	{
		return new Result\Model(false, 'NotAuthorized', $service);
	}

	public function MaxUploadSizeExceeded($service)
	{
		return new Result\Model(false, 'MaxUploadSizeExceeded', $service);
	}

	public function Evaluate($file_path)
	{
		try {
			return new Result\EvaluateFile($file_path);
		}
		catch (\Pomegranate\framework\Exception $ex) {
			return new Result\Model(false, $ex->getMessage());
		}
	}

	public function Read($file_path)
	{
		try {
			return new Result\LocalFile($file_path);
		}
		catch (\Pomegranate\framework\Exception $ex) {
			return new Result\Model(false, $ex->getMessage());
		}
	}

	public function PackJSClasses(array $class_paths)
	{
		try {
			return new Result\PackJsClasses($class_paths);
		}
		catch (\Pomegranate\framework\Exception $ex) {
			return new Result\Model(false, $ex->getMessage());
		}
	}
}
