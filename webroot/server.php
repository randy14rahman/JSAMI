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

require_once 'config.php';
require_once 'session.php';

$class_path = $_REQUEST['Path'];
$request_type = $_REQUEST['Request'];
$response_type = $_REQUEST['Response'];
$raw_index = $_REQUEST['RawIndex'];

$server = new \Pomegranate\framework\GenericServer($class_path, $request_type, $response_type, $raw_index);

$server->handle();
