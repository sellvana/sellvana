define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'wishlist_feed', callback: channel_wishlist_feed});

    function channel_wishlist_feed(msg) {

        switch (msg.signal) {
            case 'new_wishlist':
                var w = msg.wishlist_info;
                $.bootstrapGrowl(w.mes_be + ' ' + w.p_name + ' ' + w.mes_af , {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
