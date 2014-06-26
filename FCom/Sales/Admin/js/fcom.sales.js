define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'sales_feed', callback: channel_sales_feed});

    function channel_sales_feed(msg) {
        switch (msg.signal) {
            case 'new_order':
                var o = msg.order;
                var href = FCom.base_href + 'orders/form/?id=' + o.id;
                var cLink = '<a href="' + href + '">#' + o.id + '</a>';
                $.bootstrapGrowl(o.mes_be + ' ' + cLink + ' ' + o.mes_af + ' ' + o.name, {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
