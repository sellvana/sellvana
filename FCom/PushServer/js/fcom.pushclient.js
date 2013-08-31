define(['jquery', 'underscore', 'exports'], function($, _, exports)
{
    var i, j, config = {}, state = { seq: 0, sub_id: 0 }, channels = {}, subscribers = {}, messages = [];

    setTimeout(start, 200); // give opportunity for other services to listen and send initial messages

    function start(options)
    {
        if (state.status) {
            return;
        }
        config = options || {};
        listen({ regexp: /^session:(.*)$/, callback: channel_session_handler });
        connect();
    }

    function connect()
    {
        for (i = 0; state.status === 'connecting' && i < 20; $i++) {
            sleep(100);
        }
        state.status = 'connecting';

        $.post(FCom.pushserver_url, { messages: messages }, receive);

        messages = $.grep(messages, function(qmsg) {
            return !(qmsg.channel === 'session' && qmsg.message === 'received');
        });
    }

    function receive(response, status, xhr)
    {
        if (!response.messages) {
            state.status = 'disconnected';
            return;
            //TODO: reconnect?
        }
        state.status = 'connected';
        $.each(response.messages, function(i, msg) {
            dispatch(msg);
            if (state.status === 'connected') {
                connect();
            }
        })
    }

    function send(msg)
    {
        if (!msg.seq) msg.seq = ++state.seq;
        messages.push(msg);
    }

    function dispatch(msg)
    {
        if (channels[msg.channel]) {
            $.each(channels[msg.channel].subscribers, function(i, sub) {
                sub.callback(msg);
            });
        }
        $.each(channel.subscribers, function(i, sub) {
            if (sub.regexp && sub.regexp.test(msg.channel)) {
                sub.callback(msg);
            }
        })
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

    function channel_session_handler(msg)
    {
        if (!state.session_id) {
            var m = msg.channel.match(/^session:(.*)$/);
            state.session_id = m[1];
        }
        switch (msg.message) {
            case 'received':
                messages = $.grep(messages, function(qmsg) {
                    return qmsg.seq != msg.seq;
                });
                break;

            case 'stop':
                state.status = 'disconnected';
                break;
        }
    }

    _.extend(exports, {
        state: state,
        start: start,
        listen: listen,
        forget: forget,
        send: send
    });
});
