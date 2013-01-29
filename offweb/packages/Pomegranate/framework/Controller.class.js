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

Pomegranate.extend(".Pomegranate.framework.MvcBase", __CLASS__, {
	init: function(parent) {
		this._super(parent);
		this.controllers = {};
		this.views = {};
	}

	//Controller loader.
	//input: { context, id, class_path, onload, params }
	, controller: function(input) {
		var that_c = this;

		if (input.context) {
			input.base = this.getNamespacePath(input.context);
			delete input["context"];
		}

		if (input.params && !jQuery.isArray(input.params))
			input.params = [input.params];

		if (input.id && that_c.controllers[input.id]) {
			if (jQuery.isFunction(input.onload)) {
				if (input.params)
					input.onload.apply(that_c.controllers[input.id], input.params);
				else
					input.onload.call(that_c.controllers[input.id]);
			}
		}
		else
			Pomegranate.create(input.base, input.class_path, [this], function() {
				if (input.id) {
					this.id = input.id;
					that_c.controllers[input.id] = this;
				}
				else {
					this.id = that_c.randomControllerId();
					that_c.controllers[this.id] = this;
				}

				if (jQuery.isFunction(input.onload)) {
					if (input.params)
						input.onload.apply(this, input.params);
					else
						input.onload.call(this);
				}
			});
	}

	//View loader.
	//input: { context, id, class_path, onload, params }
	, view: function(input) {
		var that_c = this;

		if (input.context) {
			input.base = this.getNamespacePath(input.context);
			delete input["context"];
		}

		if (input.params && !jQuery.isArray(input.params))
			input.params = [input.params];

		if (input.id && that_c.views[input.id]) {
			if (jQuery.isFunction(input.onload)) {
				if (input.params)
					input.onload.apply(that_c.views[input.id], input.params);
				else
					input.onload.call(that_c.views[input.id]);
			}
		}
		else
			Pomegranate.create(input.base, input.class_path, [this], function() {
				if (input.id) {
					this.id = input.id;
					that_c.views[input.id] = this;
				}
				else {
					this.id = that_c.randomViewId();
					that_c.views[this.id] = this;
				}

				if (jQuery.isFunction(input.onload)) {
					if (input.params)
						input.onload.apply(this, input.params);
					else
						input.onload.call(this);
				}
			});
	}

	//Model loader.
	//input: { context, class_path, method, params, callback }
	, model: function(input) {
		if (input.context) {
			input.base = this.getNamespacePath(input.context);
			delete input["context"];
		}
		return Pomegranate.model(input);
	}

	//Sends a command request. Each command request is identical to a model request except that it will not wait for a package to form, even if the 'PackModelRequests' is set.
	//input: { context, class_path, method, params, callback }
	, command: function(input) {
		if (input.context) {
			input.base = this.getNamespacePath(input.context);
			delete input["context"];
		}
		return Pomegranate.command(input);
	}

	//input: { context, class_path, method, params, callback }
	, download: function(input) {
		if (input.context) {
			input.base = this.getNamespacePath(input.context);
			delete input["context"];
		}
		return Pomegranate.download(input);
	}
	
	//input: { context, class_path, method, params, files: { field-name: file }, callback, failed, aborted, progress }
	, upload: function(input, files) {
		if (input.context) {
			input.base = this.getNamespacePath(input.context);
			delete input["context"];
		}
		return Pomegranate.upload(input, files);
	}

	, getPackage: function() {
		return this.parentController.getPackage();
	}

	, getParent: function() {
		return this.parentController;
	}

	, generateRandId: function() {
		return "rndid_" + (Math.floor(Math.random() * 1000000000));
	}

	, randomControllerId: function() {
		var id = null;
		do {
			id = this.generateRandId();
		} while (this.controllers[id]);
		return id;
	}

	, randomViewId: function() {
		var id = null;
		do {
			id = this.generateRandId();
		} while (this.views[id]);
		return id;
	}

	, removeChild: function(Object) {
		for (var index in this.views)
			if (this.views[index] == Object) {
				delete this.views[index];
				break;
			}
		for (var index in this.controllers)
			if (this.controllers[index] == Object) {
				delete this.controllers[index];
				break;
			}
	}
	
	, dispose : function() {
		this._super();
		for (var index in this.controllers)
			this.controllers[index].destroy();
		this.controllers = [];
		for (var index in this.views)
			this.views[index].destroy();
		this.views = [];
		this.getParent().removeChild(this);
	}
	
	, getNamespacePath: function(path) {
		var namespace_pieces = path.split(".");
		namespace_pieces.pop();
		return namespace_pieces.join(".");
	}
	
	, models: function(inputs, callback) {
		var that_c = this;
		Pome.create(undefined, ".Pomegranate.framework.SignalCounter", [inputs.length], function() {
			var responses = [];
			for (var index in inputs)
				responses.push(undefined);

			var thread = this;
			thread.addHandler(function() {
				callback.apply({}, [responses]);
			});
			
			var _cb = function(response, id) {
				for (var index in inputs) {
					if (id == inputs[index].id) {
						responses[index] = response;
						break;
					}
				}
				thread.signal();
			}

			for (var index in inputs) {
				inputs[index].callback = _cb;
				that_c.model(inputs[index]);
			}
		});
	}
});