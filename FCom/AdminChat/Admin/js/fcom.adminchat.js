
define(['jquery', 'underscore', 'backbone', 'fcom.pushclient', 'exports'], function($, _, Backbone, PushClient, exports)
{
    var chatWindows = {};

    // send to server
    function status(options) {
        PushClient.send({channel:'adminchat', signal:'status', status:options.status});
    }

    function start(options) {
        PushClient.send({channel:'adminchat', signal:'start', user:options.user});
    }

    function invite(options) {
        PushClient.send({channel:'adminchat', signal:'invite', user:options.user});
    }

    function say(options) {
        PushClient.send({channel:options.channel, signal:'say', text:options.text});
    }

    function leave(options) {
console.log('leave', options);
        PushClient.send({channel:options.channel, signal:'leave'});
    }

    function show_window(chat) {
console.log('show_window', chat);
        var $container = $('<div class="fcom-chat-window-container">').attr('id', chat.channel+'-container')
            .css({right:(10+_.size(chatWindows)*220)+'px'});
        var $innerContainer = $('<div class="fcom-chat-window-inner">').appendTo($container);
        var $title = $('<div class="fcom-chat-title">').html(chat.channel).appendTo($innerContainer);
        var $closeTrigger = $('<a href="#" class="fcom-chat-close-trigger">X</a>').appendTo($innerContainer);
        var $history = $('<div class="fcom-chat-history">').html(chat.history).appendTo($innerContainer);
        var $input = $('<input class="fcom-chat-input">').appendTo($innerContainer)

        $closeTrigger.click(function(ev) { leave({channel:chat.channel}); });
        $input.keydown(function(ev) {
            var text = $input.val();
            if (text && ev.which==13) {
                say({channel:chat.channel, text:text});
                $input.val('');
            }
        });

        chatWindows[chat.channel] = {$container:$container, $history:$history};

        $container.appendTo('body');
    }

    // receive from server
    PushClient.listen({ channel: 'adminchat', callback: channel_adminchat});
    PushClient.listen({ regexp: /^adminchat:(.*)$/, callback: channel_adminchat});

    function channel_adminchat(msg)
    {
        console.log(msg.signal, msg);
        if (channel_adminchat.signals[msg.signal]) {
            channel_adminchat.signals[msg.signal](msg);
        }
    }

    channel_adminchat.signals = {
        chats: function(msg) {
            _.each(msg.chats, function(chat) {
                show_window(chat);
            })
        },
        start: function(msg) {
            show_window({channel:msg.channel});
        },
        say: function(msg) {
            var $h = chatWindows[msg.channel].$history;
            $h.append($('<div>').html(msg.text))
            $h.css({scrollTop:10000});
        },
        leave: function(msg) {

        }
    }

    _.extend(exports, {
        status: status,
        start: start,
        invite: invite,
        say: say,
        leave: leave
    });
});

