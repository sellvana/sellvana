define(["jquery", "fcom.frontend"], function ($) {

    FCom.CartWidget = function (opt) {
        opt = opt || {};
        var cartActiveTimeout;

        function add(id, qty) {
            if (!opt.apiUrl) opt.apiUrl = FCom.base_href + "cart/addxhr";
            $.post(opt.apiUrl, {action: 'add', id: id, qty: qty || 1}, function (data) {
                //console.log(data);
                /*
                $.pnotify({pnotify_title:data.title,
                    pnotify_text:'<div class="ui-pnotify ui-widget ui-helper-clearfix" style="min-height: 56px; width: 300px; opacity: 1; display: block; right: 15px; top: 15px;">'+data.html+'</div>'});
                */
                $('.cart-num-items').html(data.cnt);
                $('#cart-subtotal').html(data.subtotal);
                $('#cart-num-items').html(data.cnt);
                $('#minicart-container').html(data.minicart_html);
                $('.mini-cart').addClass('active');
                clearTimeout(cartActiveTimeout);
                cartActiveTimeout = setTimeout(function () {
                    $('.mini-cart').removeClass('active');
                }, 3000);
            });
        }

        return {add: add};
    };

    FCom.cart = new FCom.CartWidget();

});
