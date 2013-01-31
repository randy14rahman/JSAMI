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

Pome.extend(".Pomegranate.framework.View", __CLASS__, {
	show: function() {
		var that_v = this;

		that_v.$loginForm = $("<form action='/'></form>")
			.submit(function(event) { that_v.amiLogin(event); })
			.append("<h1>AMI Login:</h1>")
			.append("WebSocket Server Host: <input type='text' name='host' value='jsami.local' /><br />")
			.append("WebSocket Server Port: <input type='text' name='port' value='8000' /><br />")
			.append("Username: <input type='text' name='username' /><br />")
			.append("Secret: <input type='text' name='secret' /><br />")
			.append("<input type='submit' value='Connect' />")
			.appendTo("body");

		that_v.$callForm = $("<form action='/'></form>")
			.submit(function(event) { that_v.originateCall(event); })
			.append("<h1>Originate A Call:</h1>")
			.append("From Extension: <input type='text' name='extension' placeholder='e.g. sip/100' /><br />")
			.append("Call Destination: <input type='text' name='phone-number' placeholder='e.g. sip/101' /><br />")
			.append("Caller ID: <input type='text' name='caller-id' placeholder='e.g. 100' /><br />")
			.append("<input type='submit' value='Call' />")
			.appendTo("body");
	}
	
	, amiLogin: function(event) {
		event.preventDefault();
		var that_v = this;
		var host = that_v.$loginForm.find("input[name='host']").val()
			, port = that_v.$loginForm.find("input[name='port']").val()
			, username = that_v.$loginForm.find("input[name='username']").val()
			, secret = that_v.$loginForm.find("input[name='secret']").val();
		that_v.trigger("Login", { host: host, port: port, username: username, secret: secret });
	}
	
	, originateCall: function(event) {
		event.preventDefault();
		var that_v = this;
		var extension = that_v.$callForm.find("input[name='extension']").val()
			, phone_number = that_v.$callForm.find("input[name='phone-number']").val()
			, caller_id = that_v.$callForm.find("input[name='caller-id']").val();
		that_v.trigger("Call", { extension: extension, phone_number: phone_number, caller_id: caller_id });
	}
});
