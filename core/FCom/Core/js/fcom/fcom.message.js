define(['jquery', 'react'], function ($, React) {

    if (!Array.isArray) {
        Array.isArray = function (arg) {
            return Object.prototype.toString.call(arg) === '[object Array]';
        };
    }
    var Messages;

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
                    needRender = true;
                    messagesQueue.push(messages[key]);
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
                var messageConfig = messagesQueue[index];

                messageConfig.key = 'FcomMessage-' + index;
                messageConfig.messageIndex = index;
                messagesNodes.push(React.createElement(
                    FcomMessage,
                    messageConfig
                ));
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
     */
    var FcomMessage = React.createClass({
            displayName: "FcomMessage",
            getDefaultProps: function () {
                return {
                    type: 'info',
                    class: 'info',
                    title: 'Info',
                    icon: 'info'
                }
            },
            render: function () {
                var props = this.props,
                    nodes = [];

                nodes.push(React.createElement('a',
                    {
                        key: 'close-link',
                        className: 'close',
                        'data-dismiss': 'alert',
                        href: '#'
                    },
                    "\u00D7"
                ));

                if (props.title) {
                    nodes.push(React.createElement('h4', {key: 'title'}, [
                        React.createElement('i', {
                            key: 'icon',
                            className: 'icon-' + props.icon + '-sign'
                        }),
                        ' ' + props.title
                    ]));
                }

                //TODO: check if exist - props.msgs
                nodes.push(props.msg);


                return React.createElement('div',
                    {
                        className: 'alert alert-dismissable alert-' + props.class,
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

        if (null !== messagesDom) {
            Messages = React.render(
                React.createElement(FcomMessages, {key: 'messages'}),
                messagesDom
            );

            Messages.pushMessage(messages);
        }
    }

    function push(messages) {
        if (undefined !== messages) {
            Messages.pushMessage(messages);
        }
    }

    return {
        init: init,
        push: push
    }
})
;