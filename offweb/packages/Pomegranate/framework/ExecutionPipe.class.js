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
    init: function(caller, interval) {
        this._super();
        var pipe = this;
        pipe.caller = caller;
        pipe.interval = interval;
        pipe.queue = [];
        pipe.intervalID = null;

        if (!pipe.interval || pipe.interval <= 0) {
            pipe.add = function(item) {
                pipe.caller.call([item]);
            };
        }
        else {
            pipe.add = function(item) {
                pipe.queue.push(item);
            };

            var exec = function() {
                var copy = pipe.queue.slice(0);
                pipe.queue = [];
                pipe.caller(copy);
            };

            pipe.intervalID = setInterval(exec, pipe.interval);
        }
    }

    , stop: function() {
        var pipe = this;
        if (pipe.intervalID !== null) {
            clearInterval(pipe.intervalID);
            pipe.intervalID = null;
        }
    }
});