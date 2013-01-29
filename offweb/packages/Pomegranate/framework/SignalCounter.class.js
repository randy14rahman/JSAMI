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

Pomegranate.extend(".Class", __CLASS__, {
	init: function(threshold) {
		this._super();
		this.counter = 0;
		this.handlers = [];
		this.params = [];
		this.threshold = threshold;
	}

	, signal: function() {
		this.counter++;
		if (this.threshold == this.counter && this.handlers.length > 0)
			this.execute();
	}

	, addHandler: function(h, p) {
		p = p || [];
		if (this.threshold <= this.counter) {
			var obj = {};
			if (!p)
				this.params.push([]);
			else if (jQuery.isArray(p))
				this.params.push(p);
			else
				h.apply(obj, [p]);
		}
		else {
			this.handlers.push(h);
			if (!p)
				this.params.push([]);
			else if (jQuery.isArray(p))
				this.params.push(p);
			else
				this.params.push([p]);
		}
	}

	, execute: function() {
		for (var index in this.handlers) {
			var obj = {};
			this.handlers[index].apply(obj, this.params[index]);
		}
	}
});