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
    init: function(parent) {
        this._super();
        this.parentController = parent;
        //Collection of event handlers to be used in internal 'bind' and 'trigger' functions.
        this.eventHandlers = {};
        this.eventContexts = {};
        if (!this.parentController) {
            this.parentController = Pomegranate;
        }
    }

    , getObjectID: function() {
        return this.id;
    }

    //Binds a callback function to a named event.
    , bind: function(name, func, cont) {
        if (typeof this.eventHandlers[name] == "undefined") {
            this.eventHandlers[name] = [func];
            this.eventContexts[name] = [cont];
        }
        else {
            var found = false;
            for (var index in this.eventHandlers[name]) {
                if (this.eventHandlers[name][index] == func && this.eventContexts[name][index] == cont) {
                    found = true;
                    break;
                }
            }
            if (!found) {
                this.eventHandlers[name].push(func);
                this.eventContexts[name].push(cont);
            }
        }
    }

    //Triggers the list of registered callback functions for a named event.
    , trigger: function(name, args) {
        if (typeof this.eventHandlers[name] != "undefined") {
            for (var index in this.eventHandlers[name]) {
                var obj = this.eventContexts[name][index];
                this.eventHandlers[name][index].apply(obj, [args]);
            }
        }
        else
            Pomegranate.console.warn("Class Path: " + this.getNamespace().getPath() + "." + this.className + "\n" + "No event handler found with name: " + name);
    }

    , unbind: function(name, func, cont) {
        if (name && !func)
            delete this.eventHandlers[name];
        else if (name && func) {
            if (this.eventHandlers[name]) {
                for (var index in this.eventHandlers[name]) {
                    if (this.eventHandlers[name][index] == func && this.eventContexts[name][index] == cont) {
                        delete this.eventHandlers[name][index];
                        delete this.eventContexts[name][index];
                        break;
                    }
                }
            }
        }
    }
    
    , destroy: function() {
        if (this._super()) {
            this.trigger("Destroyed", this);
        }
    }
});