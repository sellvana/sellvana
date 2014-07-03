define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'reviews_feed', callback: channel_product_reviews_feed});

    function channel_product_reviews_feed(msg) {

        switch (msg.signal) {
            case 'new_review':
                var mes = msg.review;
                var text = mes.href ? $('<a>').attr('href', FCom.base_href + mes.href).html(mes.text) : $('<div>').html(mes.text);
                $.bootstrapGrowl(text, mes.growl_params || {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
