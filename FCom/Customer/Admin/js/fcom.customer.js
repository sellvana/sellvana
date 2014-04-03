define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function($, PushClient) {

    PushClient.send({channel: 'client', signal: 'subscribe', to: 'customers_feed'});
    PushClient.listen({channel: 'customers_feed', callback: channel_customers});

    function channel_customers(msg) {
        switch (msg.signal) {
            case 'new_customer':
                var c = msg.customer;
                $.bootstrapGrowl('New customer registration! <a href="' + c.href + '">'
                    + c.name + ' (' + c.email + ')</a>', {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
