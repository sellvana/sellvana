define(['jquery', 'jquery.cookie', 'jquery.tablesorter'], function ($) {

    FCom.CompareBlock = function (opt) {
        //console.log('FROM CATALOG.JS', $, $.cookie);
        var cookieName = opt.cookieName || 'fulleronCompare', cookie = $.cookie(cookieName);
        var selected = cookie ? JSON.parse(cookie) : [], ul = $('ul', opt.thumbContainer);
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
            $(opt.checkbox + '[value=' + id + ']').attr('checked', value);
            if (!value) added[id] = false;
        }

        function notify(s) {
            if (added[s.id]) return;
            added[s.id] = true;
            //$.pnotify({pnotify_title:'Added to compare', pnotify_text:'<div style="width:100%;overflow:auto"><img src="'+s.src+'" width="35" height="35" style="float:left"/> '+s.alt+'</div>'});
        }

        function add(id) {
            if (selected.length == 4) {
                alert("Can not add more than 4 to compare");
                return false;
            }
            var img = $(opt.prodContainerPrefix + id + ' ' + opt.img);
            if (!img) img = $('p.product-img img');
            var s = {id: id, src: img.attr('src'), alt: img.attr('alt')};
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
            return true;
        }

        function remove(id, trigger) {
            var i, ii;
            for (i = 0, ii = selected.length; i < ii; i++) if (selected[i].id == id) break;
            if (i == selected.length) return false;

            $(ul.children().get(i)).remove();
            ul.append('<li/>');
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
            return true;
        }

        function reset() {
            for (var i = 0; i < 4; i++) {
                if (!selected.length) break;
                remove(selected[0].id);
            }
        }

        function toggle(id) {
            return remove(id) || add(id);
        }

        if (opt.thumbContainer) {
            for (var i = 0; i < selected.length; i++) {
                thumb(selected[i], i);
                check(selected[i].id, true);
            }
            if (selected.length) $(opt.thumbContainer).addClass('set');
        }
        if (opt.checkbox) $(function () {
            $(opt.checkbox).click(function (ev) {
                return toggle(ev.target.value)
            });
        });

        $('[rel=#compare-overlay]', opt.thumbContainer).mousedown(function () {
            if (selected.length < 2) {
                alert('Please select at least two products to compare');
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
