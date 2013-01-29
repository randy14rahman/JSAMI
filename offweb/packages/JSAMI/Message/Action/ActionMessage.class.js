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

Pomegranate.extend(".JSAMI.Message.OutgoingMessage", __CLASS__, {
	init: function(what) {
		this._super();
		this.setKey("Action", what);
		this.setActionID(this.createdDate + Math.random());
	}
	, setActionID: function(actionID) {
		actionID = actionID + "";
		if (0 == actionID.length) {
			throw "ActionID cannot be empty.";
		}

		if (actionID.length > 69) {
			throw "ActionID can be at most 69 characters long.";
		}

		this.setKey("ActionID", actionID);
	}
});