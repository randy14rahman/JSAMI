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
	init: function(options, login_callback, login_context) {
		this._super();
		this.socket = null;
		this._incomingQueue = {};
		this._lastActionId = null;
		this._responseCallbacks = {};
		this._evenHandlers = [];

		if (!window.WebSocket) {
			throw "AMIClient relies on WebSocket which can not be found in this browser.";
		}
		else if (!options.host) {
			throw "AMIClient needs to know the address to Asterisk's proxy host.";
		}

		this.options = options;

		//Try to connect
		this.connect(login_callback, login_context);
	}

	, connect: function(login_callback, login_context) {
		var address = (this.options.secure ? "wss://" : "ws://")
			+ this.options.host
			+ (this.options.port ? ":" + this.options.port : "")
			+ (this.options.application ? "/" + this.options.application : "/ws");
		var sub_protocol;
		if (this.options.protocol === undefined) {
			sub_protocol = "ami";
		}
		else if (this.options.protocol === false || this.options.protocol === "") {
			sub_protocol = undefined;
		}
		else {
			sub_protocol = this.options.protocol;
		}

		this.close();

		if (sub_protocol) {
			this.socket = new WebSocket(address, sub_protocol);
		}
		else {
			this.socket = new WebSocket(address);
		}
		var _this = this;
		this.socket.onopen = function() {
			if (_this.options.username) {
				Pomegranate.create(undefined, ".JSAMI.Message.Action.LoginAction", [_this.options.username, _this.options.password], function() {
					_this.send(this, function(response) {
						if (!response.isSuccess()) {
							_this.close();
						}
						if (jQuery.isFunction(login_callback)) {
							login_callback.apply(login_context ? login_context : {}, [response]);
						}
					});
				});
			}
			else if (jQuery.isFunction(login_callback)) {
				login_callback.apply(login_context ? login_context : {}, [response]);
			}
		}
		this.socket.onclose = function() {
			_this.socket = null;
		}
		this.socket.onmessage = function(message) {
			var resPos = message.data.search(/Response:/i);
			var evePos = message.data.search(/Event:/i);
			if ((resPos !== -1) && ((resPos < evePos) || evePos === -1)) {
				Pomegranate.create(undefined, ".JSAMI.Message.Response.ResponseMessage", [message.data], function() {
					var response = this;
					if (response.getActionId() === null) {
						response.setActionId(_this._lastActionId);
					}
					if (response.completed) {
						var obj = _this._responseCallbacks[response.getActionId()];
						if (obj) {
							obj.callback.apply(obj.context ? obj.context : {}, [response]);
						}
						if (_this._incomingQueue[response.getActionId()]) {
							delete _this._incomingQueue[response.getActionId()];
						}
					}
					else {
						_this._incomingQueue[response.getActionId()] = response;
					}
				});
			}
			else if (evePos !== -1) {
				Pomegranate.create(undefined, ".JSAMI.Message.Event.EventMessage", [message.data], function() {
					var event = this;
					var response = false;
					if (_this._incomingQueue[event.getActionId()]) {
						response = _this._incomingQueue[event.getActionId()];
					}
					if (response === false || response.completed) {
						if (response !== false) {
							delete _this._incomingQueue[response.getActionId()];
						}
						for (var index in _this._evenHandlers) {
							var obj = _this._evenHandlers[index];
							obj.callback.apply(obj.context ? obj.context : {}, [event]);
						}
					}
					else {
						response.addEvent(event);
						if (response.completed) {
							var obj = _this._responseCallbacks[response.getActionId()];
							if (obj) {
								obj.callback.apply(obj.context ? obj.context : {}, [response]);
							}
							delete _this._incomingQueue[response.getActionId()];
						}
					}
				});
			}
			else {
				// broken ami.. sending a response with events without
				// Event and ActionId
				var bMsg = "Event: ResponseEvent\r\n";
				bMsg += "ActionId: " + _this._lastActionId + "\r\n" + message.data;
				Pomegranate.create(undefined, ".JSAMI.Message.Event.EventMessage", [bMsg], function() {
					var event = this;
					if (_this._incomingQueue[event.getActionId()]) {
						_this._incomingQueue[event.getActionId()].addEvent(event);
					}
				});
			}
		}
	}

	, send: function(action, callback, context) {
		if (this.socket !== null) {
			var msg = action.serialize();
			this._lastActionId = action.getActionId();
			if (jQuery.isFunction(callback)) {
				this._responseCallbacks[action.getActionId()] = { callback: callback, context: context };
			}
			this.socket.send(msg);
		}
		else {
			throw "No connection found to send the message to.";
		}
	}

	, close: function() {
		if (this.socket !== null) {
			this.socket.close();
		}
	}

	, isConnected: function() {
		return this.socket !== null;
	}

	, registerEventHandler: function(callback, context) {
		if (jQuery.isFunction(callback)) {
			this._evenHandlers.push({ callback: callback, context: context });
		}
	}

	, unregisterEventHandler: function(callback) {
		for (var index in this._evenHandlers) {
			if (this._evenHandlers[index].callback === callback) {
				this._evenHandlers.splice(index, 1);
			}
		}
	}
});
