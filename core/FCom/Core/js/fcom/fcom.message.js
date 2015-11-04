define(['jquery', 'react'], function ($, React) {

    if (!Array.isArray) {
        Array.isArray = function (arg) {
            return Object.prototype.toString.call(arg) === '[object Array]';
        };
    }
    var Messages = {};

    var FcomMessages = React.createClass({
        displayName: "FcomMessagesQueue",
        getDefaultProps: function () {
            return {
                "messages": []
            }
        },
        pushMessage: function (messages) {
            if (undefined === messages) {
                return;
            }

            var needRender;

            var messagesQueue = this.props.messages;

            if (Array.isArray(messages)) {
                for (var key in messages) {
                    if (messages.hasOwnProperty(key)) {
                        needRender = true;
                        messagesQueue.push(messages[key]);
                    }
                }
            } else {
                needRender = true;
                messagesQueue.push(messages);
            }

            if (needRender) {
                this.forceUpdate();
            }
        },
        render: function () {
            var messagesNodes = [];
            var messagesQueue = this.props.messages;

            for (var index in messagesQueue) {
                if (messagesQueue.hasOwnProperty(index)) {

                    var messageConfig = {};
                    //debugger;
                    if (undefined !== messagesQueue[index].type) {
                        messageConfig.type = messagesQueue[index].type;
                    }
                    messageConfig.config = messagesQueue[index];
                    messageConfig.key = 'FcomMessage-' + index;
                    messageConfig.messageIndex = index;
                    messagesNodes.push(React.createElement(
                        FcomMessage,
                        messageConfig
                    ));
                }
            }

            return React.createElement('div', {key: 'messagesQueue'}, messagesNodes);
        }
    });

    /**
     * types:
     *   success
     *   info
     *   warning
     *   danger
     *   error
     */
    var FcomMessage = React.createClass({
            displayName: "FcomMessage",
            getDefaultProps: function () {
                return {
                    type: 'info',
                    types: {
                        success: {class: 'success', title: 'Success', icon: 'ok'},
                        info: {class: 'info', title: 'Info', icon: 'info'},
                        warning: {class: 'warning', title: 'Warning', icon: 'exclamation'},
                        danger: {class: 'danger', title: 'Danger', icon: 'remove'},
                        error: {class: 'danger', title: 'Danger', icon: 'remove'}
                    }
                }
            },
            render: function () {
                var props = this.props,
                    type = this.props.type,
                    config,
                    nodes = [];

                config = $.extend({msg: null, msgs: null}, props.types[type], props['config']);

                nodes.push(React.createElement('a',
                    {
                        key: 'close-link',
                        className: 'close',
                        'data-dismiss': 'alert',
                        href: '#'
                    },
                    "\u00D7"
                ));

                if (config.title) {
                    nodes.push(React.createElement('h4', {key: 'title'}, [
                        React.createElement('i', {
                            key: 'icon',
                            className: 'icon-' + config.icon + '-sign'
                        }),
                        ' ' + config.title
                    ]));
                }

                if (null != config.msgs) {
                    for (var id in config.msgs) {
                        if (config.msgs.hasOwnProperty(id)) {
                            nodes.push(config.msgs[id]);
                            nodes.push(React.createElement('br', {key: 'br' + id}));
                        }
                    }
                    nodes.pop();
                } else {
                    nodes.push(config.msg);
                }

                return React.createElement('div',
                    {
                        className: 'alert alert-dismissable alert-' + config.class,
                        id: 'import-log',
                        key: 'message'
                    },
                    nodes
                );
            }
        })
        ;

    function init(messagesDomId, messages) {
        var messagesDom = document.getElementById(messagesDomId);

        this.id = messagesDomId;

        if (null !== messagesDom) {
            Messages[messagesDomId] = React.render(
                React.createElement(FcomMessages, {key: 'messages'}),
                messagesDom
            );

            Messages[messagesDomId].pushMessage(messages);
        }
    }

    function push(messages) {
        if (undefined !== messages) {
            Messages[this.id].pushMessage(messages);
        }
    }

    return {
        init: init,
        push: push,
        id: null
    }
});