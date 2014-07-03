define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'customers_feed', callback: channel_customers_feed});

    function channel_customers_feed(msg) {
        switch (msg.signal) {
            case 'new_customer':
                var mes = msg.customer;
                var text = mes.href ? $('<a>').attr('href', FCom.base_href + mes.href).html(mes.text) : $('<div>').html(mes.text);
                $.bootstrapGrowl(text, mes.growl_params || {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
