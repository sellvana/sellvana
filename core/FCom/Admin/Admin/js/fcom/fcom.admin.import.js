define(['jquery', 'react', 'underscore', 'fcom.pushclient', 'exports', 'fcom.core'], function ($, React, _, PushClient, exports) {

    var FcomAdminImportLog = React.createClass({
        displayName: "FcomAdminImportLog",
        getDefaultProps: function () {
            return {
                "pushServerObjects": [],
                "currentModel": null
            }
        },
        /**
         * string data.objectId @see php FCom_Core_ImportExport::_currentObjectId
         * string data.signal [info, problem, start, finished]
         * string data.msg
         * undefined|array data.data
         */
        handleMessage: function (data) {
            var that = this,
                objects = this.props.pushServerObjects;

            if (objects[data.objectId] === undefined) {
                objects[data.objectId] = {};
                //objects[data.objectId] = React.createElement(FcomAdminImportPushServerObjects, {msg: data});
            }
            var currentObject = objects[data.objectId];


            switch (data.signal) {
                case 'start':
                    that.handleStartSignal(data, currentObject);
                    break;
                case 'info':
                    that.handleInfoSignal(data, currentObject);
                    break;
                case 'finished':
                    that.handleFinishedSignal(data, currentObject);
                    break;
            }
            console.log('alert ', objects);
            this.forceUpdate();
        },
        handleStartSignal: function (message, item) {
            var data = this.getMessageData(message);

            if (data.fileName !== undefined) {
                item.title = data.fileName;
            }
        },
        handleInfoSignal: function (message, item) {
            var data = this.getMessageData(message);

            if (data.storeId !== undefined) {
                item.storeId = data.storeId;
            }

            if (data.startModel !== undefined) {
                var modelName = data.startModel;
                this.setModelData(modelName, null, item);
                this.props.currentModel = modelName;
            }

            if (data.modelStatistic !== undefined) {
                this.setModelData(data.currentModel, {info: data.modelStatistic}, item);
            }
        },
        handleFinishedSignal: function (message, item) {
            var that = this,
                data = this.getMessageData(message);

            if (data.modelsStatistic !== undefined) {
                for (key in data.modelsStatistic) {
                    that.setModelData(key, {info: data.modelsStatistic[key]}, item);
                }
            }
        },
        setModelData: function (modelName, data, item) {
            if (data === undefined || data === null) {
                data = {};
            }
            if (item.models === undefined) {
                item.models = {}
            }
            if (item.models[modelName] === undefined) {
                item.models[modelName] = {};
            }

            var model = item.models[modelName];

            if (model.name === undefined) {
                model.name = modelName;
            }

            if (data.info !== undefined) {
                model.info = data.info;
            }
        },
        getMessageData: function (message) {
            var data = message.data;

            if (data === undefined) {
                data = {};
            }
            return data;
        },
        render: function () {
            var nodes = [];
            for (var key in this.props.pushServerObjects) {
                nodes.push(this.props.pushServerObjects[key]);
            }
            return React.createElement('div', null, nodes);
        }
    });

    var FcomAdminImportPushServerObjects = React.createClass({
        displayName: "FcomAdminImportPushServerObjects",
        render: function () {
            return React.createElement('div', null, this.props.msg);
        }
    });

    function init(logCanvas) {
        var logDom = document.getElementById(logCanvas);

        var log = React.render(
            React.createElement(FcomAdminImportLog, {logBox: logCanvas}),
            logDom
        );

        PushClient.listen({channel: 'import', callback: log.handleMessage});
    }

    _.extend(exports, {
        init: init
    });
});
