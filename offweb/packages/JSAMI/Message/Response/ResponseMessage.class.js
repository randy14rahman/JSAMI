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

Pomegranate.extend(".JSAMI.Message.IncomingMessage", __CLASS__, {
	init: function(rawContent) {
		this._super(rawContent);
		this.events = [];
		this.completed = false;
		this.completed = !this.isList();
	}
	, addEvent: function(event) {
		this.events.push(event);
		if ((event.getEventList() && event.getEventList().search(/complete/i) !== -1)
		|| (event.getName() && event.getName().search(/complete/i) !== -1)
		|| (event.getName() && event.getName().search(/DBGetResponse/i) !== -1)) {
			this.completed = true;
		}
	}
	, isSuccess: function() {
		return this.getKey("Response") && this.getKey("Response").search(/Error/i) === -1;
	}
	, isList: function() {
		return (this.getKey("EventList") && this.getKey("EventList").search(/start/i) !== -1)
			|| (this.getMessage() && this.getMessage().search(/follow/i) !== -1);
	}
	, getMessage: function() {
		return this.getKey("Message");
	}
	, setActionId: function(actionId) {
		this.setKey("ActionId", actionId);
	}
});
