define(["jquery"/*, "backbone", "transparency", "jquery.widgets"*/],

function($/*, Backbone*/) {
    /*
    FCom.tabs = function(options) {
        var tabs = $(options.tabs);
        var curLi = $(options.tabs+'[class=active]');
        var curPane = $(options.panes+':not([hidden])');

        $('a', tabs).click(function(ev) {
            curLi.removeClass('active');
            curPane.removeClass('active');
            ev.stopPropagation();

            var a = $(ev.currentTarget), li = a.parent('li');
            if (curLi===li) {
                return false;
            }
            var pane = $(a.attr('href'));
            li.addClass('active');
            pane.addClass('active');
            curLi = li;
            curPane = pane;
            var tabId = a.attr('href').replace(/^#/,'');
            return false;
        });
    }
    */

    function addslashes(str) {
        return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
    }

    function setUrlParam(uri, params) {
        for (var key in params) {
            value = params[key];
            var re = new RegExp("([?|&])" + key + "=.*?(&|$)", "i");
            separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                uri = uri.replace(re, value === false ? '' : '$1' + key + "=" + value + '$2');
            } else {
                uri = uri + separator + key + "=" + value;
            }
        }
        return uri;
    }

    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': csrfToken
        }
    });

    FCom._ = function (str) {
        return FCom.i18n[str] || str;
    };
    /*
    FCom.TransparencyView = Backbone.View.extend({
        constructor: function (options) {
            Backbone.View.prototype.constructor.apply(this, arguments);
            this.setElement($(this.options.baseEl).clone());
            this.model.on("change", this.render, this);
        },

        render: function () {
            Transparency.render(this.el, this.model.toJSON());
        }
    });
    */
    $(function () {
        $('form').append($('<input type="hidden" name="X-CSRF-TOKEN"/>').val(csrfToken));
        if ($.fn.select2) {
            $('.select2').select2({width: 'other values', minimumResultsForSearch: 20, dropdownAutoWidth: true});
        }
    })
});

function partial(el, options) {
    el = $(el);
    if (!el.length) return;
    var req = [], i, params = el.data('params'), scroll = $('.scrollable', el).scrollTop();
    params = params || {};
    options = options || {};
    if (options.reset || !el.data('params')) el.data('params', {});
    options.src = options.src || el.data('src');
    if (options.params) {
        for (i in options.params) {
            params[i] = options.params[i];
        }
        el.data('params', params);
    }
    for (i in params) {
        req.push(encodeURIComponent(i) + '=' + encodeURIComponent(params[i]));
    }
    el.css({opacity: .5});
    el.load(options.src + (options.src && options.src.match(/\?/) ? '&' : '?') + req.join('&'), function (data) {
        $('.scrollable', el).scrollTop(scroll);
        el.css({opacity: 1});
        if (typeof options.complete !== 'undefined') options.complete();
    });
}

function partialParent(el, params) {
    partial($(el).closest('.include'), params);
}
