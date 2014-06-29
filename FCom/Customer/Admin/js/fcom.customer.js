define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'customers_feed', callback: channel_customers_feed});

    function channel_customers_feed(msg) {
        switch (msg.signal) {
            case 'new_customer':
                var c = msg.customer;
                var href = FCom.base_href + 'customers/form/?id=' + c.id;
                var cLink = '<a href="' + href + '">' + c.name + ' (' + c.email + ')</a>';
                $.bootstrapGrowl(cLink + ' ' + c.mes, {type: 'success', align: 'center', width: 'auto'});
                break;
            case 'new_login':
                var l = msg.login_info;
                var href = FCom.base_href + 'customers/form/?id=' + l.id;
                var cLink = '<a href="' + href + '">' + l.name + ' (' + l.email + ')</a>';
                $.bootstrapGrowl(cLink + ' ' + l.mes, {type: 'success', align: 'center', width: 'auto'});
                break;
            case 'new_subscription':
                var s = msg.subscription;
                var href = FCom.base_href + 'subscriptions';
                var cLink = '<a href="' + href + '">(' + s.email + ')</a>';
                $.bootstrapGrowl(cLink + ' ' + s.mes, {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
