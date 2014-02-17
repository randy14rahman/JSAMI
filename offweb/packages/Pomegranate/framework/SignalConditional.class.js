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
    init: function(conditions, firstLevelOperator) {
        this._super();
        this.operator = [firstLevelOperator || "and"];
        this.operator[1] = this.operator[0] == "and" ? "or" : "and";

        if (typeof conditions == "string") {
            conditions = [conditions];
        }

        this.conditions = conditions;
        this.grantedConditions = [];
        this.handlers = [];
        this.params = [];
    }

    , pruneConditions: function(level, stageConditions) {
        if (stageConditions.length == 0)
            return;

        var indicesToRemove = [];
        var removeAll = false;
        for (var index in stageConditions)
            if (typeof stageConditions[index] == "string") {
                var found = false;
                for (var index2 in this.grantedConditions)
                    if (stageConditions[index] == this.grantedConditions[index2]) {
                        found = true;
                        break;
                    }
                if (found) {
                    if (this.operator[level] == "and")
                        indicesToRemove.push(index);
                    else {
                        removeAll = true;
                        break;
                    }
                }
            }
            else {
                this.pruneConditions(level+1 % 2, stageConditions[index]);
                if (stageConditions[index].length == 0) {
                    if (this.operator[level] == "and")
                        indicesToRemove.push(index);
                    else {
                        removeAll = true;
                        break;
                    }
                }
            }

        if (removeAll)
            stageConditions.splice(0, stageConditions.length);
        else if (indicesToRemove.length > 0)
            for (var index in indicesToRemove)
                stageConditions.splice(indicesToRemove[index], 1);
    }

    , signal: function(granted) {
        if (typeof granted == "string")
            granted = [granted];
        this.grantedConditions = this.grantedConditions.concat(granted);

        this.pruneConditions(0, this.conditions);

        if (this.conditions.length == 0 && this.handlers.length > 0)
            execute();
    }

    , addHandler: function(handler, params) {
        params = params || [];
        if (this.conditions.length == 0) {
            var obj = {};
            if (!params)
                this.params.push([]);
            else if (jQuery.isArray(params))
                this.params.push(params);
            else
                handler.apply(obj, [params]);
        }
        else {
            this.handlers.push(handler);
            if (!params)
                this.params.push([]);
            else if (jQuery.isArray(params))
                this.params.push(params);
            else
                this.params.push([params]);
        }
    }

    , execute: function() {
        for (var index in this.handlers) {
            var obj = {};
            this.handlers[index].apply(obj, this.params[index]);
        }
    }
});