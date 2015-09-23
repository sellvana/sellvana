define(['jquery', 'react', 'underscore', 'fcom.pushclient', 'exports', 'fcom.locale', 'fcom.core'], function ($, React, _, PushClient, exports, Locale) {

    if (!Array.isArray) {
        Array.isArray = function (arg) {
            return Object.prototype.toString.call(arg) === '[object Array]';
        };
    }

    var FcomAdminImportStatistic = React.createClass({
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
            var boxClass = ['box', 'box-nomargin'],
                boxHeader,
                boxHeaderNodes = [],
                boxHeaderActions = null,
                boxContent,
                boxContentNodes = [];

            for (var key in this.props.pushServerObjects) {
                var pushServerObject = this.props.pushServerObjects[key];

                boxContentNodes.push(React.createElement(FcomAdminImportPushServerObject, {
                    title: pushServerObject.title,
                    storeId: pushServerObject.storeId,
                    models: pushServerObject.models
                }));
            }

            if (boxContentNodes.length == 0) {
                boxClass.push('box-collapsed');
            } else {
                boxHeaderActions = React.createElement('div', {className: 'actions'}, [
                    React.createElement('a', {className: 'btn box-collapse btn-xs btn-link', href: '#'},
                        React.createElement('i', {className: 'icon-chevron-down'})
                    )
                ]);
                boxContent = React.createElement('div', {className: 'box-content'}, boxContentNodes)
            }

            boxHeaderNodes.push(
                React.createElement('div', {className: 'title'}, Locale._('Import Statistic'))
            );

            if (boxHeaderActions !== null) {
                boxHeaderNodes.push(boxHeaderActions);
            }

            boxHeader = React.createElement('div', {className: 'box-header'}, boxHeaderNodes);


            /*return React.createElement('div', {className: 'row', id: 'import-log'},
             React.createElement('div', {className: 'col-sm-12'},
             React.createElement('div', {className: boxClass.join(' ')}, [
             boxHeader,
             boxContent
             ])
             )
             );*/
            return React.createElement(FcomAdminBox, {}, boxHeaderNodes);
        }
    });

    var FcomAdminImportPushServerObject = React.createClass({
        displayName: "FcomAdminImportPushServerObject",
        getDefaultProps: function () {
            return {
                'title': null,
                'storeId': null,
                'models': {}
            }
        },
        render: function () {
            var props = this.props,
                modelNodes = [];

            var rowId = 1;

            for (var key in props.models) {
                var model = props.models[key],
                    modelInfoNodes = [];

                modelInfoNodes.push(
                    React.createElement('td', null, rowId++)
                );

                modelInfoNodes.push(
                    React.createElement('td', null, model.name)
                );

                var keys = [
                    'newModels',
                    'updatedModels',
                    'notChanged'
                ];

                for (var index in keys) {
                    var key = keys[index],
                        value = 0;

                    if (model.info !== undefined && model.info[key] !== undefined) {
                        value = model.info[key];
                    }

                    modelInfoNodes.push(
                        React.createElement('td', {className: key}, value)
                    );
                }
                modelNodes.push(
                    React.createElement('tr', null, modelInfoNodes)
                );
            }
            return React.createElement('div', null, [
                props.title + " - " + props.storeId,
                React.createElement('div', {className: 'responsive-table'},
                    React.createElement('div', {className: 'scrollable-area'},
                        React.createElement('table', {className: 'table table-bordered table-hover table-striped'}, [
                            React.createElement('thead', null, [
                                React.createElement('tr', null, [
                                    React.createElement('th', null, " "),
                                    React.createElement('th', null, Locale._('Model Name')),
                                    React.createElement('th', null, Locale._('New')),
                                    React.createElement('th', null, Locale._('Updated')),
                                    React.createElement('th', null, Locale._('Not Changed'))
                                ])
                            ]),
                            React.createElement('tbody', null, modelNodes)
                        ])
                    )
                )
            ]);
        }
    });

    /*    var FcomAdminImportLog = React.createClass({
     displayName: "FcomAdminImportLog",
     handleMessage: function(data){

     },
     render: function(){
     var boxClass = ['box', 'box-nomargin'],
     boxHeader,
     boxHeaderNodes = [],
     boxHeaderActions = null,
     boxContent,
     boxContentNodes = [];

     if (boxContentNodes.length == 0) {
     boxClass.push('box-collapsed');
     } else {
     boxHeaderActions = React.createElement('div', {className: 'actions'}, [
     React.createElement('a', {className: 'btn box-collapse btn-xs btn-link', href: '#'},
     React.createElement('i', {className: 'icon-chevron-down'})
     )
     ]);
     boxContent = React.createElement('div', {className: 'box-content'}, boxContentNodes)
     }

     boxHeaderNodes.push(
     React.createElement('div', {className: 'title'}, Locale._('Import Log'))
     );

     if (boxHeaderActions !== null) {
     boxHeaderNodes.push(boxHeaderActions);
     }

     boxHeader = React.createElement('div', {className: 'box-header'}, boxHeaderNodes);


     return React.createElement('div', {className: 'row', id: 'import-log'},
     React.createElement('div', {className: 'col-sm-12'},
     React.createElement('div', {className: boxClass.join(' ')}, [
     boxHeader,
     boxContent
     ])
     )
     );
     }
     });*/

    var FcomAdminBox = React.createClass({
        displayName: "FcomAdminBox",
        getDefaultProps: function () {
            return {
                className: ['box', 'box-nomargin'],
                additionalClassName: null,
                title: '',
                collapsible: false,
                isCollapsed: true,
                headerClassName: ['box-header']
            }
        },
        _getBoxClassArray: function () {
            var classes = [];

            if (typeof this.props.className === 'string') {
                alert(1);
                classes = classes.concat(this.props.className.split(" "));
            }
            if (Array.isArray(this.props.className)) {
                //alert(2);
                classes = classes.concat(this.props.className);
            }

            if (this.props.additionalClassName !== null) {
                alert(3);
                if (typeof this.props.additionalClassName === 'string') {
                alert(4);
                    classes = classes.concat(this.props.additionalClassName.split(" "));
                }
                if (Array.isArray(this.props.additionalClassName)) {
                alert(5);
                    classes = classes.concat(this.props.additionalClassName);
                }
            }

            return classes;
        },
        render: function () {
            var props = this.props,
                boxClass = this._getBoxClassArray(),
                boxHeader,
                boxHeaderNodes = [],
                boxHeaderActions = [],
                boxContent;

            if (props.collapsible) {
                boxHeaderActions.push(
                    React.createElement('a', {className: 'btn box-collapse btn-xs btn-link', href: '#'},
                        React.createElement('i', {className: 'icon-chevron-down'})
                    )
                );
                if (props.isCollapsed) {
                    boxClass.push('box-collapsed');
                }
            }

            boxHeaderActions = React.createElement('div', {className: 'actions'}, boxHeaderActions);

            boxHeaderNodes.push(
                React.createElement('div', {className: 'title'}, props.title)
            );

            boxHeaderNodes.push(boxHeaderActions);

            boxHeader = React.createElement('div', {className: props.headerClassName.join(' ')}, boxHeaderNodes);

            boxContent = React.createElement('div', {className: 'box-content'}, this.props.children);

            console.log('asdf', boxClass);
            return React.createElement('div', {className: boxClass.join(' '), id: props.id}, [
                boxHeader,
                boxContent
            ]);
        }
    });

    function log(msg) {
        console.log('alog', msg);
    }

    function init(statisticCanvas, logCanvas) {
        var statisticDom = document.getElementById(statisticCanvas),
            logDom = document.getElementById(logCanvas),
            importStatistic = React.render(
                React.createElement(FcomAdminImportStatistic, null),
                statisticDom
            );
            //importLog = React.render(
            //    React.createElement(FcomAdminImportLog, null),
            //    logDom
            //);

        PushClient.listen({channel: 'import', callback: importStatistic.handleMessage});
        //PushClient.listen({channel: 'import', callback: importLog.handleMessage});
    }

    _.extend(exports, {
        init: init
    });
});
