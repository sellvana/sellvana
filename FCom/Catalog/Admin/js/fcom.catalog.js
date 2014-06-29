define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'catalog_feed', callback: channel_catalog_feed});

    function channel_catalog_feed(msg) {

        switch (msg.signal) {
            case 'new_search':
                var s = msg.search_info;
                var key = '<i>' + s.key + '</i>';
                $.bootstrapGrowl(s.mes_be + ' ' + key + ' ' + s.mes_af , {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
