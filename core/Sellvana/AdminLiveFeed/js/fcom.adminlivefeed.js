define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    var numItems = 0;

    PushClient.listen({channel: 'activities_feed', callback: channel_activities_feed});

    function channel_activities_feed(msg) {
        var notifType = msg.notif_type || 'realtime';

        var text = msg.href ? $('<a>').attr('href', FCom.base_href + msg.href).html(msg.content) : $('<div>').html(msg.content);
        $.bootstrapGrowl(text, msg.growl_params || {type:'success', align:'center', width:'auto'});

        var $body = $('<div class="widget-body">'), $icon = $('<i>');
        if (msg.icon_class) {
            $icon.addClass(msg.icon_class);
        }
        $body.append($('<div class="pull-left icon">').html($icon));
        $body.append($('<div class="pull-left text">').html(msg.content));
        $body.append($('<small class="pull-right text-muted timeago">').attr('title', msg.ts).html(msg.ts));

        var $a = $('<a>').attr('href', msg.href ? FCom.base_href + msg.href : '#').attr('title', msg.title).html($body);

        var $li = $('<li>').html($a), elId = '#header-notifications-' + notifType;
        $(elId).css({display:'block'});
        $(elId + '-count').html(++numItems);
        $(elId + ' .dropdown-menu').prepend('<li class="divider"></li>').prepend($li);
        $(elId + ' .dropdown-toggle i').effect('highlight', {}, 1500);
    }
})
