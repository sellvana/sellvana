define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'activities_feed', callback: channel_activities_feed});

    function channel_activities_feed(msg) {
        switch (msg.signal) {
            case 'new_login':
                var l = msg.login_info;
                var href = FCom.base_href + 'customers/form/?id=' + l.id;
                var cLink = '<a href="' + href + '">' + l.name + ' (' + l.email + ')</a>';
                $.bootstrapGrowl(cLink + ' ' + l.mes, {type: 'success', align: 'center', width: 'auto'});
                break;
            case 'new_wishlist':
                var w = msg.wishlist_info;
                $.bootstrapGrowl(w.mes_be + ' ' + w.p_name + ' ' + w.mes_af , {type: 'success', align: 'center', width: 'auto'});
                break;
            case 'new_subscription':
                var s = msg.subscription;
                var href = FCom.base_href + 'subscriptions';
                var cLink = '<a href="' + href + '">(' + s.email + ')</a>';
                $.bootstrapGrowl(cLink + ' ' + s.mes, {type: 'success', align: 'center', width: 'auto'});
                break;
            case 'new_search':
                var s = msg.search_info;
                var key = '<i>' + s.key + '</i>';
                $.bootstrapGrowl(s.mes_be + ' ' + key + ' ' + s.mes_af , {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
