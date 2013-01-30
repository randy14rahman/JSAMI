<?php
/**
 *   Copyright 2013 Mehran Ziadloo & Siamak Sobhany
 *   JSAMI: A Javascript client to Asterisk's AMI
 *   (https://github.com/ziadloo/JSAMI)
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
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<link rel="shortcut icon" href="/favicon.ico">
<title>JSAMI Project</title>
<style>
</style>

<script src="/url:resource/Pomegranate/resources/javascripts/jquery-1.7.2.min.js"></script>
<script src="/url:resource/Pomegranate/resources/javascripts/jquery.json-2.3.min.js"></script>
<script src="/url:resource/Pomegranate/framework/OOP.js"></script>
<script src="/url:resource/Pomegranate/framework/Pomegranate.js"></script>

<style>
html, body {
	width: 100%;
	height: 100%;
	margin: 0px;
	padding: 0px;
}
</style>

</head>

<body>
<script>
Pomegranate.init(function() {
	Pomegranate.package({
		id: "pkgJSAMITest"
		, class_path: ".Example.MainController"
		, onload: function() {
			window.JSAMITest = this;
			this.main();
		}
	});
});
</script>
</body>

</html>