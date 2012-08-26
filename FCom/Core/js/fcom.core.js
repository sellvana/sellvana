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

FCom._ = function(str) {
return FCom.i18n[str] || str;
}

function addslashes(str) {
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}