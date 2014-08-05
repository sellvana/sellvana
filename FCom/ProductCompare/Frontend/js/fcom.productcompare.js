define(['jquery', 'jquery.cookie', 'jquery.tablesorter', 'fcom.locale'], function ($) {

    FCom.CompareBlock = function (opt) {
        //console.log('FROM CATALOG.JS', $, $.cookie);
        var cookieName = opt.cookieName || 'sellvana_compare', cookie = $.cookie(cookieName);
//        var selected = cookie ? JSON.parse(cookie) : [], ul = $('ul', opt.thumbContainer);
        var selected, ul = $('ul', opt.thumbContainer);
        var limit = opt.limitCompare || 5;
        var urlAdd = opt.url_add || '/catalog/compare/add';
        var urlRm = opt.url_remove || '/catalog/compare/rm';
        if (opt.productIds) {
            selected = opt.productIds;
        } else {
            selected = cookie ? JSON.parse(cookie) : [];
        }
        var added = {}; // to avoid duplicate notifications

        function thumb(s, i) {
            console.log(s, i);
            var a = $('<a href="#" title="' + s.alt + '"><img src="' + s.src + '" width="35" height="35" alt="' + s.alt + '"/></a>');
            a.click(function () {
                remove(s.id);
                return false
            });
            $(ul.children().get(i)).append(a);
        }

        function check(id, value) {
            $(opt.checkbox + '[data-id=' + id + ']').each(function () {
                toggleLink($(this));
            });
            if (!value) {
                added[id] = false;
            }
        }

        function notify(s) {
            if (added[s.id]) {
                return;
            }
            added[s.id] = true;
            //$.pnotify({pnotify_title:'Added to compare', pnotify_text:'<div style="width:100%;overflow:auto"><img src="'+s.src+'" width="35" height="35" style="float:left"/> '+s.alt+'</div>'});
        }

        function add(id) {
            if (selected.length == limit) {
                alert(locale._("Max number of products to compare is ") + limit);
                return false;
            }

//            var s = {id: id, src: img.attr('src'), alt: img.attr('alt')};
            var add = true;
            $.get(urlAdd, {id: id}, function(result){
                if(result.hasOwnProperty('product')) {
                    var s = result.product;
                    selected.push(s);
                    thumb(s, selected.length - 1);
                    check(id, true);
                    $.cookie(cookieName, JSON.stringify(selected), {expires: 1});
                    $('.compare-num-products').html(selected.length);
                    $(opt.thumbContainer).addClass('set');
                    console.log('animate start');
                    $(opt.thumbContainer).stop().animate({boxShadow: '0px 0px 15px #A2C2EA'}, 1000, function () {
                        console.log('animate stop');
                        $(opt.thumbContainer).stop().animate({boxShadow: '0px 0px'}, 1000);
                    });
                    //humanMsg.displayMsg('<img src="'+s.src+'" width="35" height="35"/> Added to compare: '+s.alt);
                    notify(s);
                } else {
                    var add = false;
                    alert(result.error);
                }
            });

            return add;
        }

        function remove(id, trigger) {
            var i, ii;
            for (i = 0, ii = selected.length; i < ii; i++) {
                if (selected[i].id == id) {
                    break;
                }
            }
            if (i == selected.length) {
                return false;
            }
            var rm = true;
            $.get(urlRm, {id: id}, function(result){
                if(result.hasOwnProperty('success')) {
                    $('a', ul.children().get(i)).remove();
//                    ul.append('<li/>');
                    check(id, false);
                    selected.splice(i, 1);
                    $.cookie(cookieName, JSON.stringify(selected), {expires: 1});

                    if (trigger) {
                        var colIdx = $(trigger).closest('th,td').get(0).cellIndex;
                        var rows = $(trigger).closest('tbody').find('tr');
                        for (i = 0, ii = rows.length; i < ii; i++) {
                            $($(rows[i]).children('th,td').get(colIdx)).remove();
                        }
                    }
                    $('.compare-num-products').html(selected.length);
                    if (selected.length < 2) {
                        //if (opt.emptyUrl) location.href = opt.emptyUrl;
                        var el = $('a[rel=#compare-overlay]').data('overlay');
                        el && el.close();
                    }
                    if (!selected.length) {
                        $(opt.thumbContainer).removeClass('set');
                    }
                } else {
                    rm = false;
                    alert(result.error);
                }
            });

            return rm;
        }

        function reset() {
            for (var i = 0; i < limit; i++) {
                if (!selected.length) {
                    break;
                }
                remove(selected[0].id);
            }
        }

        function toggle(id) {
            return remove(id) || add(id);
        }

        if (opt.thumbContainer) {
//            for (var i = 0; i < selected.length; i++) {
//                thumb(selected[i], i);
//                check(selected[i].id, true);
//            }

            for (var i in selected) {
                if (selected.hasOwnProperty(i)) {
                    thumb(selected[i], i);
                    check(selected[i].id, true);
                }
            }
            if (selected.length) {
                $(opt.thumbContainer).addClass('set');
            }
        }

        if (opt.checkbox) {
            $(opt.checkbox).click(function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var $self = $(this);
                var value = $self.attr('data-id');
//                toggleLink($self);
                return toggle(value)
            });
        }

        function toggleLink($self) {
            var $icon = $self.find('span');
            var checked = $icon.hasClass('glyphicon-unchecked');
            if (checked) {
                $icon.removeClass('glyphicon-unchecked').addClass('glyphicon-check');
            } else {
                $icon.removeClass('glyphicon-check').addClass('glyphicon-unchecked');
            }
        }

        $('[rel=#compare-overlay]', opt.thumbContainer).mousedown(function () {
            if (selected.length < 2) {
                alert(locale._("Please select at least two products to compare"));
                return false;
            }
            return true;
        });
        $('.reset-btn', opt.thumbContainer).click(function () {
            reset();
            return false;
        });

        return {add: add, remove: remove, toggle: toggle, reset: reset};
    }

});
