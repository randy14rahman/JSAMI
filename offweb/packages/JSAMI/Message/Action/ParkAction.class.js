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

Pomegranate.extend(".JSAMI.Message.Action.ActionMessage", __CLASS__, {
	init: function(channel1, channel2, timeout, lot) {
		this._super("Park");
		timeout = timeout || false;
		lot = lot || false;
		this.setKey("Channel", channel1);
		this.setKey("Channe2", channel2);
		if (timeout) {
			this.setKey("Timeout", timeout);
		}
		if (lot) {
			this.setKey("Parkinglot", lot);
		}
	}
});
