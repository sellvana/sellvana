define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'activities_feed', callback: channel_activities_feed});

    function channel_activities_feed(msg) {
        var text = msg.href ? $('<a>').attr('href', FCom.base_href + msg.href).html(msg.text) : $('<div>').html(msg.text);
        $.bootstrapGrowl(text, msg.growl_params || {type:'success', align:'center', width:'auto'});
    }
})
