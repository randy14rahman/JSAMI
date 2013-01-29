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

Pome.extend(".Pomegranate.framework.Controller", __CLASS__, {
	main: function() {
		var that_c = this;
		that_c.view({
			context: __CLASS__
			, id: "vwMain"
			, class_path: "MainView"
			, onload: function() {
				this.show();
				this.bind("Login", that_c.onLogin, that_c);
				this.bind("Call", that_c.onCall, that_c);
			}
		});
	}

	//input: { host, port, username, secret }
	, onLogin: function(input) {
		var that_c = this;
		Pome.create(undefined, ".JSAMI.Connection", [
				{
					host: input.host
					, port: input.port
					, application: "ami"
					, protocol: false
					, username: input.username
					, password: input.secret
				}
				, function(response) {
					//Login callback
					if (!response.isSuccess()) {
						alert("Problem with login");
						that_c.jsami = null;
					}
					else {
						alert("Logged in successfully");
					}
				}
			], function() {
			//This function is called when an instatiation of .JSAMI.Connection is created
			that_c.jsami = this;
		});
	}

	//input: { extension, phone_number, caller_id }
	, onCall: function(input) {
		var that_c = this;
		if (!that_c.jsami || !that_c.jsami.isConnected()) {
			alert("You need to login before you can originate a call");
			return;
		}
		Pome.create(undefined, ".JSAMI.Message.Action.OriginateAction", [input.extension], function() {
			this.setContext("custom-callboth");
			this.setPriority("1");
			this.setTimeout("30");
			this.setExtension(input.phone_number);
			this.setCallerId(input.caller_id);
			that_c.jsami.send(this);
		});
	}
});
