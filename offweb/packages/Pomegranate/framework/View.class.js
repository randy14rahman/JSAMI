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
    }

    , getPackage: function() {
        return this.parentController.getPackage();
    }

    , getParent: function() {
        return this.parentController;
    }

    , dispose: function() {
        this._super();
        this.getParent().removeChild(this);
    }

    , resource: function(input) {
        if (input.context) {
            input.base = this.getNamespacePath(input.context);
            delete input["context"];
        }
        return Pomegranate.resource(input);
    }

    , getNamespacePath: function(path) {
        var namespace_pieces = path.split(".");
        namespace_pieces.pop();
        return namespace_pieces.join(".");
    }
});