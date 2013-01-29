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

Pomegranate.extend(".JSAMI.Message.Message", __CLASS__, {
	init: function(rawContent) {
		this._super();
		this.rawContent = null;
		this.rawContent = rawContent + "";
		var lines = rawContent.split(this.EOL);
		var lines2 = rawContent.split("\n");
		if (lines2.length > lines.length) {
			lines = lines2;
		}
		for (var index in lines) {
			var content = lines[index].split(":");
			var name = jQuery.trim(content[0]).toLowerCase();
			content.splice(0, 1);
			var value = jQuery.trim(content.join(":"));
			this.setKey(name, value);
		}
	}
	, getEventList: function() {
		return this.getKey("EventList");
	}
});
