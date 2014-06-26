define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'reviews_feed', callback: channel_product_reviews_feed});

    function channel_product_reviews_feed(msg) {
        switch (msg.signal) {
            case 'new_review':
                var r = msg.review;
                var href = FCom.base_href + 'catalog/products/form/?id=' + r.id;
                var cLink = '<a href="' + href + '">#' + r.id + '</a>';
                $.bootstrapGrowl(r.name + ' ' + r.mes + ' ' + cLink, {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
