define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function($, PushClient) {

    PushClient.listen({channel: 'customers_feed', callback: channel_customers_feed});

    function channel_customers_feed(msg) {
        switch (msg.signal) {
            case 'new_customer':
                var c = msg.customer;
                var href = FCom.base_href + 'customers/form/?id=' + c.id;
                var cLink = '<a href="' + href + '">' + c.name + ' (' + c.email + ')</a>';
                $.bootstrapGrowl('New customer registration! ' + cLink, {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
