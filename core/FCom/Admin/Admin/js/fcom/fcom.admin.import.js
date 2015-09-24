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
            var contentNodes = [];

            for (var key in this.props.pushServerObjects) {
                var pushServerObject = this.props.pushServerObjects[key];

                contentNodes.push(React.createElement(FcomAdminImportPushServerObject, {
                    key: key,
                    title: pushServerObject.title,
                    storeId: pushServerObject.storeId,
                    models: pushServerObject.models
                }));
            }

            if (contentNodes.length == 0) {
                collapsible = false;

                contentNodes.push(
                    React.createElement('span', {key: 'noData'}, Locale._('No data'))
                );
            }

            return React.createElement('div', {className: 'row', id: 'import-log'},
                React.createElement('div', {className: 'col-sm-12'},
                    React.createElement(FcomAdminBox, {
                        id: 'import-log',
                        additionalClassName: null,
                        title: Locale._('Import Statistic'),
                        collapsible: true,
                        isCollapsed: false
                    }, contentNodes)
                )
            );
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
                    React.createElement('td', {key: key + '-nr'}, rowId++)
                );

                modelInfoNodes.push(
                    React.createElement('td', {key: key + '-name'}, model.name)
                );

                var keys = [
                    'newModels',
                    'updatedModels',
                    'notChanged'
                ];

                for (var index in keys) {
                    var tkey = keys[index],
                        value = 0;

                    if (model.info !== undefined && model.info[tkey] !== undefined) {
                        value = model.info[tkey];
                    }

                    modelInfoNodes.push(
                        React.createElement('td', {className: tkey, key: key + "-" + tkey}, value)
                    );
                }
                modelNodes.push(
                    React.createElement('tr', {key: key + '-tr'}, modelInfoNodes)
                );
            }
            return React.createElement(FcomAdminBox, {
                id: 'file-' + props['storeId'],
                title: props['title'] + " - " + props['storeId'],
                collapsible: true,
                isCollapsed: true
            }, [
                React.createElement('div', {className: 'responsive-table', key: 'table-area'},
                    React.createElement('div', {className: 'scrollable-area', key: 'area'},
                        React.createElement('table', {
                            className: 'table table-bordered table-hover table-striped',
                            key: 'table'
                        }, [
                            React.createElement('thead', {key: 'thead'}, [
                                React.createElement('tr', {key: 'thead-tr'}, [
                                    React.createElement('th', {key: 'th-nr'}, " "),
                                    React.createElement('th', {key: 'th-name'}, Locale._('Model Name')),
                                    React.createElement('th', {key: 'th-new'}, Locale._('New')),
                                    React.createElement('th', {key: 'th-updated'}, Locale._('Updated')),
                                    React.createElement('th', {key: 'th-not-chanched'}, Locale._('Not Changed'))
                                ])
                            ]),
                            React.createElement('tbody', {key: 'tbody'}, modelNodes)
                        ])
                    )
                )
            ]);
        }
    });

    var FcomAdminImportLog = React.createClass({
        displayName: "FcomAdminImportLog",
        getDefaultProps: function () {
            return {
                "watched": [
                    //'info'
                    'problem'
                    //,'start'
                    //,'finished'
                ]
            }
        },
        /**
         * string data.objectId @see php FCom_Core_ImportExport::_currentObjectId
         * string data.signal [info, problem, start, finished]
         * string data.msg
         * undefined|array data.data
         */
        handleMessage: function (data) {
            var logTitle = 'Import ('
                + data.signal
                + ')'
                + data.objectId
                + ': ';
            if (this.props.watched.indexOf(data.signal) != -1) {
                console.log(logTitle, data);
            }
        },
        //watched channel
        render: function () {
            var that = this;

            return React.createElement('span', {key: 'import-log'},
                Locale._('Console log enabled. Watched signals: ' + that.props.watched.join(', ')));
        }
    });

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
                classes = classes.concat(this.props.className.split(" "));
            }
            if (Array.isArray(this.props.className)) {
                classes = classes.concat(this.props.className);
            }

            if (this.props.additionalClassName !== null) {
                if (typeof this.props.additionalClassName === 'string') {
                    classes = classes.concat(this.props.additionalClassName.split(" "));
                }
                if (Array.isArray(this.props.additionalClassName)) {
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
                    React.createElement('a', {
                            className: 'btn box-collapse btn-xs btn-link',
                            href: '#',
                            key: 'btn-collapse'
                        },
                        React.createElement('i', {className: 'icon-chevron-down'})
                    )
                );
                if (props.isCollapsed) {
                    boxClass.push('box-collapsed');
                }
            }

            boxHeaderActions = React.createElement('div', {
                className: 'actions',
                key: 'header-actions'
            }, boxHeaderActions);

            boxHeaderNodes.push(
                React.createElement('div', {className: 'title', key: 'header-title'}, props.title)
            );

            boxHeaderNodes.push(boxHeaderActions);

            boxHeader = React.createElement('div', {
                className: props.headerClassName.join(' '),
                key: 'box-header'
            }, boxHeaderNodes);

            boxContent = React.createElement('div', {
                className: 'box-content',
                key: 'box-content'
            }, this.props.children);

            return React.createElement('div', {className: boxClass.join(' '), id: props.id}, [
                boxHeader,
                boxContent
            ]);
        }
    });

    function init(statisticCanvas, logCanvas) {
        var statisticDom = document.getElementById(statisticCanvas),
            logDom = document.getElementById(logCanvas),
            importStatistic = React.render(
                React.createElement(FcomAdminImportStatistic, {key: 'importStatistic'}),
                statisticDom
            );
        importLog = React.render(
            React.createElement(FcomAdminImportLog, {key: 'importlog'}),
            logDom
        );

        PushClient.listen({channel: 'import', callback: importStatistic.handleMessage});
        PushClient.listen({channel: 'import', callback: importLog.handleMessage});
    }

    _.extend(exports, {
        init: init
    });
});
