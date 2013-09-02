define(['jquery', 'underscore', 'exports', 'fcom.core'], function($, _, exports)
{
    if (!window.name) { // unique name for the browser window or tab
        window.name = Math.random();
    }
    var state = { sub_id: 0, page_id: window.name, conn_id: 0, msg_id: 0, conn_cnt: 0 },
        channels = {},
        subscribers = {},
        messages = [];

    send({channel:'session', signal:'load'});

    scheduler();

    listen({ regexp: /^./, callback: catch_all })
    listen({ channel: 'session', callback: channel_session });
    listen({ regexp: /^session:(.*)$/, callback: channel_session });

    function scheduler()
    {
        if (messages.length) {
            connect();
        }
        setTimeout(scheduler, 300);
    }

    function connect()
    {
        var data = JSON.stringify({ page_id: state.page_id, conn_id: state.conn_id++, messages: messages });

        messages = _.filter(messages, function(qmsg) { return !_.isEmpty(qmsg.seq); });
        $.post(FCom.pushserver_url, data, receive);

        state.conn_cnt++;
console.log('send', state.conn_cnt, data);
    }

    function receive(response, status, xhr)
    {
console.log('receive', state.conn_cnt, JSON.stringify(response.messages));

        _.each(response.messages, function(msg) {
            if (channels[msg.channel]) {
                _.each(channels[msg.channel].subscribers, function(sub) {
                    sub.callback(msg);
                });
            }
            _.each(subscribers, function(sub) {
                if (sub.regexp && sub.regexp.test(msg.channel)) {
                    sub.callback(msg);
                }
            });
        });

        if ((state.conn_cnt > 0) && ((--state.conn_cnt) === 0)) {
            connect();
        }
    }

    function send(msg)
    {
        if (!msg.seq) msg.seq = ++state.msg_id;
        messages.push(msg);
    }

    function listen(options)
    {
        if (!options.alias) {
            options.alias = ++state.sub_id;
        }
        if (options.regexp) {
            subscribers[options.alias] = options;
        } else if (options.channel) {
            if (!channels[options.channel]) {
                channels[options.channel] = { subscribers: {} };
            }
            channels[options.channel].subscribers[options.alias] = options;
        }
    }

    function forget(alias, channel)
    {
        if (channel) {
            delete channels[channel].subscribers[alias];
        } else {
            delete subscribers[alias];
        }
    }

    function catch_all(msg)
    {

    }

    function channel_session(msg)
    {
        switch (msg.signal) {
            case 'received':
                messages = _.filter(messages, function(qmsg) { return qmsg.seq != msg.ref_seq; });
                break;

            case 'error':
                $.bootstrapGrowl(msg.description, { type:'error', align:'center', width:'auto' });

            case 'stop':
                state.conn_cnt = 0;
                break;
        }
    }

    _.extend(exports, {
        state: state,
        listen: listen,
        forget: forget,
        send: send
    });
});
