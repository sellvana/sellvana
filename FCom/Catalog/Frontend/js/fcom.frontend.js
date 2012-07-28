function addslashes(str) {
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

function getCookie(name) {
    for(var i=0, c=document.cookie.split(';'); c && i<c.length; i++) {
        if(c[i].substr(0,c[i].indexOf('=')).replace(/^\s+|\s+$/g,'')==name) {
            return unescape(c[i].substr(c[i].indexOf('=')+1));
        }
    }
}

function setCookie(name, value, exdays) {
    var d=new Date(), exp=exdays===null?'':(value===null?'Thu, 01-Jan-1970 00:00:01 GMT':d.setDate(d.getDate()+exdays));
    var c=window.cookieDefault;
    document.cookie=name+'='+escape(value)+(exp?';expires='+exp:'')+';domain='+escape(c.domain)+';path='+escape(c.path);
}

function FulleronCompare(opt) {
    var cookieName = opt.cookieName || 'fulleronCompare', cookie = getCookie(cookieName);
    var selected = cookie ? JSON.parse(cookie) : [], ul = $('ul', opt.thumbContainer);
    var added = {}; // to avoid duplicate notifications

    function thumb(s, i) {
        var a = $('<a href="#" title="'+s.alt+'"><img src="'+s.src+'" width="35" height="35" alt="'+s.alt+'"/></a>');
        a.click(function() { remove(s.id); return false});
        $(ul.children().get(i)).append(a);
    }

    function check(id, value) {
        $(opt.checkbox+'[value='+id+']').attr('checked', value);
        if (!value) added[id] = false;
    }

    function notify(s) {
        if (added[s.id]) return;
        added[s.id] = true;
        $.pnotify({pnotify_title:'Added to compare', pnotify_text:'<div style="width:100%;overflow:auto"><img src="'+s.src+'" width="35" height="35" style="float:left"/> '+s.alt+'</div>'});
    }

    function add(id) {
        if (selected.length==4) {
            alert("Can not add more than 4 to compare");
            return false;
        }
        var img = $(opt.prodContainerPrefix+id+' '+opt.img);
        if (!img) img = $('p.product-img img');
        var s = {id:id, src:img.attr('src'), alt:img.attr('alt')};
        selected.push(s);
        thumb(s, selected.length-1);
        check(id, true);
        setCookie(cookieName, JSON.stringify(selected), 1);
        $('.compare-num-products').html(selected.length);
        $(opt.thumbContainer).addClass('set');
        $(opt.thumbContainer).stop().animate({boxShadow:'0px 0px 15px #A2C2EA'}, 1000, function() {
            $(opt.thumbContainer).stop().animate({boxShadow:'0px 0px'}, 1000);
        });
        //humanMsg.displayMsg('<img src="'+s.src+'" width="35" height="35"/> Added to compare: '+s.alt);
        notify(s);
        return true;
    }

    function remove(id, trigger) {
        for (var i=0; i<selected.length; i++) if (selected[i].id==id) break;
        if (i==selected.length) return false;
        $(ul.children().get(i)).remove(); ul.append('<li/>');
        check(id, false);
        selected.splice(i, 1);
        setCookie(cookieName, JSON.stringify(selected), 1);
        if (trigger) {
            $(trigger).parents('li').remove();
            $(trigger).parents('ul').append('<li>&nbsp;</li>');
        }
        $('.compare-num-products').html(selected.length);
        if (selected.length<2) {
            //if (opt.emptyUrl) location.href = opt.emptyUrl;
            var el = $('a[rel=#compare-overlay]').data('overlay'); el && el.close();
        }
        if (!selected.length) {
            $(opt.thumbContainer).removeClass('set');
        }
        return true;
    }

    function reset() {
        for (var i=0; i<4; i++) {
            if (!selected.length) break;
            remove(selected[0].id);
        }
    }

    function toggle(id) {
        return remove(id) || add(id);
    }

    if (opt.thumbContainer) {
        for (var i=0; i<selected.length; i++) { thumb(selected[i], i); check(selected[i].id, true); }
        if (selected.length) $(opt.thumbContainer).addClass('set');
    }
    if (opt.checkbox) $(function() {
        $(opt.checkbox).click(function(ev) { return toggle(ev.target.value) });
    });

    $('[rel=#compare-overlay]', opt.thumbContainer).mousedown(function() {
        if (selected.length<2) {
            alert('Please select at least two products to compare');
            return false;
        }
        return true;
    });
    $('.reset-btn', opt.thumbContainer).click(function() { reset(); return false; });

    return {add:add, remove:remove, toggle:toggle, reset:reset};
}

function ManufIframe(opt) {
    iframe = $(opt.iframe);

    //absolutize(el);
    var o = iframe.offset(), w = iframe.width(), h = iframe.height(), close, placeholder;

    function expand() {
        var st = $(document).scrollTop(), sl = $(document).scrollLeft(),
        ww = $(window).width(), wh = $(window).height();

        iframe.css({position:'fixed', left:o.left-sl, top:o.top-st, width:w, height:h})
        .animate({left:10, top:10, width:ww-20, height:wh-20}, function() {
            close = $('<div style="background:red;position:fixed;top:0;left:0;z-index:1001"><a href="#">[X]</a>');
            iframe.after(close);
            close.children('a').click(function() { collapse(); return false; });
            shortcut.add('Esc', function() { collapse(); });
        });
        placeholder = $('<div>').css({width:w, height:h});
        iframe.after(placeholder);
        return false;
    }

    function collapse() {
        var st = $(document).scrollTop(), sl = $(document).scrollLeft();
        close.remove();
        iframe.animate({left:o.left-sl, top:o.top-st, width:w, height:h}, function() {
            placeholder.remove();
            iframe.css({position:'static', width:'100%', height:h});
        });
        shortcut.remove('Esc');
    }

    return {expand:expand, collapse:collapse};
}

$(function(){
    $('.block-layered-nav .block-content a').hover(
          function () {
            $(this).animate({ backgroundColor: "#eee" }, 60);
          },
          function () {
            $(this).animate({ backgroundColor: "#f7f7f7" }, 30);
        }
    );
});

