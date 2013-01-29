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

Pomegranate.extend(".Class", __CLASS__, {
	EOL: "\r\n"
	, EOM: "\r\n\r\n"
	, init: function() {
		this._super();
		this.variables = {};
		this.keys = {};
		this.createdDate = null;
		this.createdDate = Math.round(new Date().getTime() / 1000);
	}
	, setVariable: function(key, value) {
		this.variables[key.toLowerCase()] = value;
	}
	, getVariable: function(key) {
		return this.variables[key.toLowerCase()];
	}
	, setKey: function(key, value) {
		this.keys[key.toLowerCase()] = value;
	}
	, getKey: function(key) {
		return this.keys[key.toLowerCase()];
	}
	, finishMessage: function(msg) {
		return msg + this.EOM;
	}
	, serializeVariable: function(key, value) {
		return "Variable: " + key + "=" + value;
	}
	, serialize: function() {
	    var result = [];
	    for (var index in this.keys) {
	        result.push(index + ": " + this.keys[index]);
	    }
	    for (var index in this.variables) {
		if (jQuery.isArray(this.variables[index])) {
			for (var index2 in this.variables[index]) {
				result.push(this.serializeVariable(index, this.variables[index][index2]));
			}
		}
		else {
			result.push(this.serializeVariable(index, this.variables[index]));
		}
	    }
	    return this.finishMessage(result.join(this.EOL));
	}
	, getActionId: function() {
		return this.getKey("ActionID");
	}
});
