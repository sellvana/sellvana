define(['jquery', 'underscore', 'exports'], function($, _, exports)
{
    var i, j, state = { seq: 0, sub_id: 0 }, channels = {}, subscribers = {}, messages = [];

    scheduler();

    listen({ channel: 'session', callback: channel_session_handler });
    listen({ regexp: /^session:(.*)$/, callback: channel_session_handler });

    function scheduler()
    {
        if (messages.length) {
            connect();
        }
        setTimeout(scheduler, 300);
    }

    function connect()
    {
        var data = JSON.stringify({ messages: messages });

        messages = $.grep(messages, function(qmsg) {
            return !_.isEmpty(qmsg.seq);
        });

        $.post(FCom.pushserver_url, data, receive);

        state.status = 'online';
    }

    function receive(response, status, xhr)
    {
        console.log(response);
        $.each(response.messages, function(i, msg) {
            if (channels[msg.channel]) {
                $.each(channels[msg.channel].subscribers, function(i, sub) {
                    sub.callback(msg);
                });
            }
            $.each(subscribers, function(i, sub) {
                if (sub.regexp && sub.regexp.test(msg.channel)) {
                    sub.callback(msg);
                }
            });
        });

        switch (state.status) {
            case 'online':
                connect();
                break;

            case 'handover':
                state.status = 'online';
                break;
        }
    }

    function send(msg)
    {
        if (!msg.seq) msg.seq = ++state.seq;
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

    function channel_session_handler(msg)
    {
        switch (msg.signal) {
            case 'received':
                messages = $.grep(messages, function(qmsg) {
                    return qmsg.seq != msg.ref_seq;
                });
                break;

            case 'handover':
                state.status = 'handover';
                break;

            case 'stop':
                state.status = 'offline';
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
