define(['jquery'], function($) {
    $('.link-wishlist').click(function(ev) {
        var $el = $(this), postData = { action: $el.data('active') ? 'remove' : 'add', id: $el.data('id') };
        $el.toggleClass('active').data('active', !$el.data('active'));
        $.post(FCom.base_href + 'wishlist', postData, function (data) {
            console.log(data);
            //TODO: revert status if failed
        });
        return false;
    })
});