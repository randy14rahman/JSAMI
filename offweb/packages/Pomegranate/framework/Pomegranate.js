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

var Pomegranate = window.Pomegranate = Pome = window.Pome = new (function() {
    //Inner object to access the global object of Pomegranate.
    var __anar = this;
    var __classTree = null;
    var __namespace = null;
    var __class = null;
    var __requestCounter = 1;
    var __modelPipe = null;
    var __loadSuspended = false;

    //Callback Queues
    var __errorCallbacks = [];
    var __modelCallbacks = [];

    //Public properties {
    __anar.packages = {};

    //By enabling debug, everything down is logged.
    __anar.debug = true;

    //Setting this flag to "true" will pack model requests and send them periodically.
    //It helps to reduce the request overhead costs.
    __anar.packModelRequests = true;

    //Once the "packModelRequests" flag is set, model requests are sent with this intervals.
    __anar.modelRequestsInterval = 500;
    
    __anar.downloadMethod = "_blank";
    //Public properties }

    //Debug Console
    __anar.console = new (function() {
        if (!window.console)
            window.console = {};
        window.console.log = window.console.log || function(){};
        window.console.warn = window.console.warn || function(){};
        window.console.error = window.console.error || function(){};
        window.console.info = window.console.info || function(){};

        var extractMessage = function(ex) {
            switch (typeof ex) {
                case "string":
                    return ex;
                    break;

                case "number":
                    return "" + ex;
                    break;

                case "object":
                    if (jQuery.isArray(ex)) {
                        return jQuery.toJSON(ex);
                    }
                    else {
                        var msg = "";
                        if (typeof ex.message == "string") {
                            msg += ex.message;
                            if (ex.stack) {
                                msg += "\n" + ex.stack.toString();
                            }
                        }
                        else
                            msg += jQuery.toJSON(ex);
                        return msg;
                    }
                    break;
            }
        };

        this.log = function(ex) {
            var msg = extractMessage(ex);
            if (__anar.debug)
                window.console.log(msg);
        };

        this.error = function(ex) {
            var msg = extractMessage(ex);
            if (__anar.debug)
                window.console.error(msg);
        };

        this.warn = function(ex) {
            var msg = extractMessage(ex);
            if (__anar.debug)
                window.console.warn(msg);
        };

        this.info = function(ex) {
            var msg = extractMessage(ex);
            if (__anar.debug)
                window.console.info(msg);
        };

        return this;
    })();

    __namespace = Class.extend({
        init: function(name, parent) {
            this.name = name;
            this.parent = parent instanceof __namespace ? parent : null;
            this.classes = {};
            this.namespaces = {};
            this.handlers = {};
        }

        , addClass: function(className, classObject) {
            classObject.className = className;
            classObject.namespace = this;
            if (!this.classes[className])
                this.classes[className] = classObject;
            if (this.handlers[className]) {
                for (var index in this.handlers[className])
                    this.handlers[className][index][0].call(this.handlers[className][index][1]);
                delete this.handlers[className];
            }
        }

        , addNamespace: function(name) {
            if (!this.namespaces[name]) {
                this.namespaces[name] = new __namespace(name, this);
            }
        }

        , getName: function() {
            return this.name;
        }

        , getPath: function() {
            if (this.parent)
                return this.parent.getPath() + "." + this.name;
            else
                return this.name;
        }

        , onLoad: function(className, handler, context) {
            context = context || {};
            if (!this.classes[className]) {
                if (!this.handlers[className])
                    this.handlers[className] = [];
                this.handlers[className].push([handler, context]);
            }
            else
                handler.call(context);
        }
    });

    __classTree = new __namespace("");
    __class = Class.extend({
        init: function() {
            this.isDestroyed = false;
        }

        , getNamespace: function() {
            return this.namespace;
        }

        //Dispose function should be called when a controller is needed no longer.
        , dispose: function() {
        }

        //Through this method, child views and controllers can be destroyed
        , destroy: function() {
            if (!this.isDestroyed) {
                this.isDestroyed = true;
                this.dispose();
                return true;
            }
            else {
                return false;
            }
        }
    });
    __class.namespace = __classTree;
    __classTree.addClass("Namespace", __namespace);
    __classTree.addClass("Class", __class);

    //input: { id, class_path, onload, params }
    __anar.package = function(input) {
        if (input.params && !jQuery.isArray(input.params))
            input.params = [input.params];

        if (input.id && __anar.packages[input.id]) {
            if (jQuery.isFunction(input.onload)) {
                if (input.params)
                    input.onload.apply(__anar.packages[input.id], input.params);
                else
                    input.onload.call(__anar.packages[input.id]);
            }
        }
        else
            Pomegranate.create(undefined, input.class_path, [], function() {
                __anar.packages[input.id] = this;
                if (jQuery.isFunction(input.onload)) {
                    if (input.params)
                        input.onload.apply(this, input.params);
                    else
                        input.onload.call(this);
                }
            });
    };

    __anar.removeChild = function(Object) {
        for (var index in __anar.packages)
            if (__anar.packages[index] == Object) {
                delete __anar.packages[index];
                break;
            }
    };

    __anar.require = function(base, classPath) {
        if (classPath[0] != "." && base)
            classPath = this.resolveRelativePath(classPath, base);
        else
            classPath = this.resolveRelativePath(classPath, ".");

        load(classPath);
    };

    __anar.create = function(base, classPath, params, onCreate) {
        if (classPath[0] != "." && base)
            classPath = this.resolveRelativePath(classPath, base);
        else
            classPath = this.resolveRelativePath(classPath, ".");

        load(classPath, function() {
            try {
                var cls = getClass(classPath);
            }
            catch (ex) {
                __anar.console.error("Even though '" +classPath+ "' was supposed to be loaded, but it was not!");
            }
            try {
                //var obj = new cls(params);
                var obj = new cls(params);
                obj.className = stripNamespacePath(classPath);
                if (onCreate && jQuery.isFunction(onCreate))
                    onCreate.call(obj);
            }
            catch (ex) {
                __anar.console.error(ex);
            }
        });
    };

    __anar.extend = function(baseClassPath, newClassPath, newClassDef, callback, context) {
        baseClassPath = this.resolveRelativePath(baseClassPath, ".");
        newClassPath = this.resolveRelativePath(newClassPath, ".");
        load(baseClassPath, function() {
            try {
                var newNamespacePath = stripClassName(newClassPath);
                var newClassName = stripNamespacePath(newClassPath);
                var newNamespace = getNamespace(newNamespacePath);
                var baseClass = getClass(baseClassPath);
                newClassDef.className = newClassName;
                newClassDef.namespace = newNamespace;
                var newClass = baseClass.extend(newClassDef);
                newNamespace.addClass(newClassName, newClass);
                if (callback && jQuery.isFunction(callback)) {
                    context = context || {};
                    callback.call(context);
                }
            }
            catch (ex) {
                __anar.console.error(ex);
            }
        });
    };

    var loadStack = [];

    load = function(path, callback) {
        path = __anar.resolveRelativePath(path, ".");
        try {
            var cls = getClass(path);
            if (callback) {
                if (jQuery.isFunction(callback))
                    callback.call({});
                else if (jQuery.isArray(callback))
                    for (var index in callback)
                        if (jQuery.isFunction(callback[index]))
                            callback[index].call({});
            }
        }
        catch (ex) {
            var loadCallback = function() {
                try {
                    for (var index=loadStack.length-1; index>=0; index--)
                        if (loadStack[index].path == path) {
                            var last = loadStack[index];
                            for (var cl in last.callback)
                                if (last.callback[cl] && jQuery.isFunction(last.callback[cl])) {
                                    var ns = getNamespace(stripClassName(last.path));
                                    var className = stripNamespacePath(last.path);
                                    ns.onLoad(className, last.callback[cl]);
                                }
                                else
                                    __anar.console.error("callback '"+path+"' is not a function!");
                            loadStack.splice(index, 1);
                        }
                }
                catch (ex) {
                    __anar.console.error(ex);
                }
            };

            var found = false;
            for (var index in loadStack)
                if (loadStack[index].path == path) {
                    found = true;
                    if (jQuery.isFunction(callback))
                        loadStack[index].callback.push(callback);
                    else if (jQuery.isArray(callback))
                        for (var index in callback)
                            if (jQuery.isFunction(callback[index]))
                                loadStack[index].callback.push(callback[index]);
                    break;
                }
            if (!found) {
                if (jQuery.isFunction(callback))
                    loadStack.push({ callback: [callback], path: path });
                else if (jQuery.isArray(callback))
                    loadStack.push({ callback: callback, path: path });
                if (!__loadSuspended) {
                    __anar.ajax("./url:class" + path.replace(/\./g, "/") + ".class.js"
                        , {
                            async: true
                            , type: "GET"
                        }
                    ).always(loadCallback).fail(failCallback);
                }
            }
        }
    };

    __anar.suspendLoad = function() {
        __loadSuspended = true;
    };

    __anar.resumeLoad = function() {
        __loadSuspended = false;

        for (var index=loadStack.length-1; index>=0; index--) {
            try {
                var cls = getClass(loadStack[index].path);
                for (var cl in loadStack[index].callback)
                    if (jQuery.isFunction(loadStack[index].callback[cl])) {
                        var ns = getNamespace(stripClassName(loadStack[index].path));
                        var className = stripNamespacePath(loadStack[index].path);
                        ns.onLoad(className, loadStack[index].callback[cl]);
                    }
                loadStack.splice(index, 1);
            }
            catch (ex) {
            }
        }

        switch (loadStack.length) {
            case 0:
                break;

            case 1:
                var _path = loadStack[0].path;
                var _callback = loadStack[0].callback;
                loadStack = [];
                load(_path, _callback);
                break;

            default:
                var _params = [];
                for (var index in loadStack) {
                    for (var cl in loadStack[index].callback)
                        if (loadStack[index].callback[cl] && jQuery.isFunction(loadStack[index].callback[cl])) {
                            var ns = getNamespace(stripClassName(loadStack[index].path));
                            var className = stripNamespacePath(loadStack[index].path);
                            ns.onLoad(className, loadStack[index].callback[cl]);
                        }
                    _params.push(loadStack[index].path);
                }
                loadStack = [];
                var data = { id: rc, method: "PackJSClasses", params: [_params] };

                var rc = __requestCounter++;
                __anar.ajax("./json:resource/Pomegranate/framework/Services"
                    , {
                        async: true
                        , type: "POST"
                        , data: jQuery.toJSON(data)
                    }
                ).fail(failCallback);
                break;
        }
    };

    //input: { server, class_path, method, params, callback }
    __anar.model = function(input) {
        if (this.packModelRequests) {
            if (input.class_path[0] != "." && input.base)
                input.class_path = this.resolveRelativePath(input.class_path, input.base);
            else
                input.class_path = this.resolveRelativePath(input.class_path, ".");
            var rc = __requestCounter++;
            input.id = rc;
            __modelPipe.add({ id: rc, server: input.server, class_path: input.class_path, method: input.method, params: input.params, callback: input.callback });
            return rc;
        }
        else
            return this.command(input);
    };
    
    __anar.models = function(inputs, callback) {
        __anar.create(undefined, ".Pomegranate.framework.SignalCounter", [inputs.length], function() {
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
                __anar.model(inputs[index]);
            }
        });
    };

    //input: { server, class_path, method, params, callback }
    __anar.command = function(input) {
        if (input.class_path[0] != "." && input.base)
            input.class_path = this.resolveRelativePath(input.class_path, input.base);
        else
            input.class_path = this.resolveRelativePath(input.class_path, ".");
        var rc = __requestCounter++;
        input.id = rc;
        this.rpcCall(input.class_path, { id: rc, method: input.method, params: input.params }, input.callback, input.server);
        return rc;
    };

    //input: { class_path, method, params, callback }
    __anar.download = function(input) {
        if (input.class_path[0] != "." && input.base)
            input.class_path = this.resolveRelativePath(input.class_path, input.base);
        else
            input.class_path = this.resolveRelativePath(input.class_path, ".");
        var rc = __requestCounter++;
        input.id = rc;

        var classPath = input.class_path
            , params = { id: rc, method: input.method, params: input.params }
            , callback = input.callback;
        
        var tempCallback = function(data) {
            modelCallback(data, callback, classPath, params);
        };

        classPath = classPath.replace(/\./g, "/");

        var url = "./json-req:resource" + classPath + "?req=" + encodeURIComponent(jQuery.toJSON(params));
        if (this.downloadMethod == "_blank") {
            window.open(url);
        }
        else if (this.downloadMethod == "_self") {
            window.location.href = url;
        }
        else {
            //iframe
            var hiddenIFrameID = "hiddenDownloader",
            iframe = document.getElementById(hiddenIFrameID);
            if (iframe === null) {
                iframe = document.createElement("iframe");
                iframe.id = hiddenIFrameID;
                iframe.style.display = "none";
                document.body.appendChild(iframe);
            }
            iframe.src = url;
        }

        return rc;
    };
    
    //input: { class_path, method, params, files: { field-name: file }, callback, aborted, progress }
    __anar.upload = function(input) {
        if (input.class_path[0] != "." && input.base)
            input.class_path = this.resolveRelativePath(input.class_path, input.base);
        else
            input.class_path = this.resolveRelativePath(input.class_path, ".");
        var rc = __requestCounter++;
        input.id = rc;
        
        var classPath = input.class_path.replace(/\./g, "/")
            , jsonrpc = { id: rc, method: input.method, params: input.params };

        var fd = new FormData();
        fd.append("jsonrpc", jQuery.toJSON(jsonrpc));
        for (var index in input.files)
            fd.append(index, input.files[index]);
        
        var xhr = new XMLHttpRequest();

        var tempCallback = function(response) {
            var data = jQuery.parseJSON(response.target.responseText);
            if (data.error) {
                failCallback(xhr, "", xhr.statusText);
            }
            if (input.callback) {
                modelCallback(data, input.callback, input.class_path, jsonrpc);
            }
        };

        xhr.addEventListener("load", tempCallback, false);
        if (input.progress)
            xhr.upload.addEventListener("progress", input.progress, false);
        if (input.aborted)
            xhr.addEventListener("abort", input.aborted, false);

        xhr.open("POST", "./json-jsonrpc:model" + classPath);
        xhr.send(fd);

        return { counter: rc, xhr: xhr };
    };

    __anar.rpcCall = function(classPath, params, callback, server) {
        var tempCallback = function() {
            try {
                if (arguments[1] == "error")
                    modelCallback(jQuery.parseJSON(arguments[0].responseText), callback, classPath, params);
                else
                    modelCallback(arguments[0], callback, classPath, params);
            }
            catch (ex) {
                __anar.console.error(ex);
            }
        };

        var query_string = "";
        if (__anar.debug) {
            query_string = window.location.search
        }

        classPath = classPath.replace(/\./g, "/");
        server = server || ".";
        __anar.ajax(server + "/json:model" + classPath + query_string
            , {
                async: true
                , dataType: "json"
                , type: "POST"
                , data: jQuery.toJSON(params)
            }
        ).always(tempCallback).fail(failCallback);
    };
    
    //input: { path, onload }
    __anar.resource = function(input) {
        var query_string = "";
        if (__anar.debug) {
            query_string = window.location.search
        }

        var resolveRelativePath = function(relativePath, basePath) {
            relativePath = relativePath || "";
            if (relativePath[0] == "/")
                return relativePath;
            else {
                var path_pieces = relativePath.split("/");
                var base_pieces = basePath.split(".");
                var all = base_pieces.concat(path_pieces);
                for (var index=0; index<all.length; index++)
                    if (all[index] == "") {
                        all.splice(index, 1);
                        index--;
                    }
                return "/" + all.join("/");
            }
        };

        if (input.path[0] != "/" && input.base)
            input.path = resolveRelativePath(input.path, input.base);
        else
            input.path = resolveRelativePath(input.path, "/");

        __anar.ajax("./url:resource" + input.path + query_string
            , {
                async: true
                , type: "GET"
            }
        ).always(function() {
            try {
                if (input.onload) {
                    input.onload.apply({}, arguments);
                }
            }
            catch (ex) {
                __anar.console.error(ex);
            }
        }).fail(failCallback);
    };

    __anar.resolveRelativePath = function(relativePath, basePath) {
        relativePath = relativePath || "";
        if (relativePath[0] == ".")
            return relativePath;
        else {
            var path_pieces = relativePath.split(".");
            var base_pieces = basePath.split(".");
            var all = base_pieces.concat(path_pieces);
            for (var index=0; index<all.length; index++)
                if (all[index] == "") {
                    all.splice(index, 1);
                    index--;
                }
            return "." + all.join(".");
        }
    };

    __anar.resetPipes = function() {
        Pomegranate.create(undefined, ".Pomegranate.framework.ExecutionPipe", [modelCaller, __anar.modelRequestsInterval], function() {
            __modelPipe = this;
        });
    };

    __anar.registerErrorCallback = function(callback) {
        __errorCallbacks.push(callback);
    };
    
    __anar.registerModelCallback = function(callback) {
        __modelCallbacks.push(callback);
    };

    var getClass = function(classPath) {
        var className = stripNamespacePath(classPath);
        var namespacePath = stripClassName(classPath);
        var namespace = getNamespace(namespacePath);
        if (namespace.classes[className])
            return namespace.classes[className];
        else
            throw "Invalid Class path, '" + className + "' was not found in '" + namespace.getPath() + "'";
    };

    var getNamespace = function(namespacePath) {
        var namespace_pieces = namespacePath.split(".");
        var currentNamespace = __classTree;
        for (var index in namespace_pieces) {
            if (namespace_pieces[index] != "") {
                if (!currentNamespace.namespaces[namespace_pieces[index]])
                    currentNamespace.addNamespace(namespace_pieces[index]);
                currentNamespace = currentNamespace.namespaces[namespace_pieces[index]];
            }
        }
        return currentNamespace;
    };

    var stripClassName = function(path) {
        var namespace_pieces = path.split(".");
        namespace_pieces.pop();
        return namespace_pieces.join(".");
    };

    var stripNamespacePath = function(path) {
        var namespace_pieces = path.split(".");
        return namespace_pieces[namespace_pieces.length - 1];
    };

    var failCallback = function(jqXHR, textStatus, errorThrown) {
        var error = errorThrown;
        try {
            if (jqXHR.responseText != "") {
                var res = jQuery.parseJSON(jqXHR.responseText);
                var error = "";
                if (jQuery.isArray(res.error)) {
                    for (var index in res.error)
                        error += "\n" + printError(res.error[index]);
                }
                else
                    error = "\n" + printError(res.error);
            }
        }
        catch (ex) {
        }
        __anar.console.error("SERVER ERROR" + error);
        try {
            for (var index in __errorCallbacks)
                __errorCallbacks[index].apply({}, [jqXHR, textStatus, errorThrown]);
        }
        catch (ex) {
            __anar.console.error(ex);
        }
    };

    //Private methods {
    //A private method to pack the list of model requests into one.
    var modelCaller = function(items) {
        if (items.length == 0)
            return;

        var orignals = {};
        var packs = {};
        for (var index in items) {
            var s = ".";
            if (items[index].server) {
                s = items[index].server;
            }
            if (!orignals[s]) {
                orignals[s] = [];
                packs[s] = [];
            }
            packs[s].push({ id: items[index].id, class_path: items[index].class_path, method: items[index].method, params: items[index].params });
            orignals[s].push({ callback: items[index].callback, class_path: items[index].class_path, params: items[index].params });
        }

        function _tempCommand(server) {
            var callback = function(data) {
                if (jQuery.isArray(data.data) || typeof data.data == "object")
                    for (var index in data.data)
                        modelCallback(data.data[index], orignals[server][index].callback, orignals[server][index].class_path, orignals[server][index].params);
            };

            __anar.command({ server: server, class_path: ".Pomegranate.framework.Services", method: "RPCUnpack", params: [packs[server]], callback: callback });
        }
        
        for (var server in packs) {
            _tempCommand(server);
        }
    };

    //Callback for handeling model requests. Here it will check if some system level error is returned.
    //In error case, an alert is shown.
    var modelCallback = function(data, callback, className, args) {
        try {
            for (var index in __modelCallbacks)
                if (!__modelCallbacks[index].apply({}, [data.result, data.id]))
                    return;
            if (callback)
                callback.apply({}, [data.result, data.id]);
        }
        catch (ex) {
            __anar.console.error(ex);
        }
    };
    
    var printError = function(error) {
        var errorMsg = "";
        errorMsg += "\n";
        errorMsg += "Type: " + error.code + "\n";
        errorMsg += error.message + "\n";

        if (jQuery.isArray(error.data) && error.data.length > 0) {
            errorMsg += "Data:\n";
            for (var key in error.data)
                errorMsg += "[" + key + "]= " + error.data[key] + "\n";
        }
        else if (typeof error.data === "object"
                && error.data !== null
                && Object.keys(error.data).length > 0) {
            errorMsg += "Data:\n";
            for (var key in error.data)
                errorMsg += key + "= " + error.data[key] + "\n";
        }
        else if (typeof error.data === "string" && error.data != "") {
            errorMsg += "Data: " + error.data + "\n";
        }
        return errorMsg;
    }
    //Private methods }

    var registery = {};

    var splitFolders = function(address) {
        var pieces = address.split(".");
        for (var index=0; index<pieces.length; index++)
            if (pieces[index] == "") {
                pieces.splice(index, 1);
                index--;
            }
        return pieces;
    };

    var getLastEntry = function(folders) {
        var cur = registery;
        var pre = null;
        var indexPre = -1;
        for (var index in folders) {
            if (pre && !pre[folders[indexPre]])
                cur = pre[folders[indexPre]] = {};
            pre = cur;
            indexPre = index;
            cur = cur[folders[index]];
        }
        return pre;
    };
    
    this.init = function(callback) {
        //An execution pipe for packing model requests.
        __anar.create(undefined, "Pomegranate.framework.ExecutionPipe", [modelCaller, __anar.modelRequestsInterval], function() {
            __modelPipe = this;
            if (callback) {
                callback.call({});
            }
        });
    }

    this.register = function(address, entry) {
        var folders = splitFolders(address);
        var pre = getLastEntry(folders);
        pre[folders[folders.length-1]] = entry;
    };

    this.unregister = function(address) {
        var folders = splitFolders(address);
        var pre = getLastEntry(folders);
        delete pre[folders[folders.length-1]];
    };

    this.isEntrySet = function(address) {
        var folders = splitFolders(address);
        var pre = getLastEntry(folders);
        return typeof pre[folders[folders.length-1]] !== "undefined";
    };

    this.getEntry = function(address) {
        var folders = splitFolders(address);
        var pre = getLastEntry(folders);
        return pre[folders[folders.length-1]];
    };
    
    this.ajax = function(url, settings) {
        return jQuery.ajax(url, settings);
    }
    
    this.breakPoint = function() {
        var dummy = 0;
    }

    return this;
})();
