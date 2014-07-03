define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'sales_feed', callback: channel_sales_feed});

    function channel_sales_feed(msg) {
        switch (msg.signal) {
            case 'new_order':
                var mes = msg.order;
                var text = mes.href ? $('<a>').attr('href', FCom.base_href + mes.href).html(mes.text) : $('<div>').html(mes.text);
                $.bootstrapGrowl(text, mes.growl_params || {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
