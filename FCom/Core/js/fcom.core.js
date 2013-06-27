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
})

FCom._ = function(str) {
return FCom.i18n[str] || str;
}

FCom.DataGrid = function(config) {
    var gridEl = $('#'+config.id);
    var gridParent = gridEl.parent();
    var gridSelection = config.selection || {};

    function load(url) {
        if (url) {
            config.grid_url = url;
        }
        gridParent.load(config.grid_url, function(response, status, xhr) {
            initDOM();
        })
    }

    function setSelection(selection)
    {
        sel = selection || gridSelection;
        for (var i in sel) {
            $('tr[data-id='+i+'] .js-sel', gridEl).prop('checked', sel[i]);
            gridSelection[i] = sel[i];
        }
        $('#'+config.id+'-selection').val(Object.keys(gridSelection).join('|'));
    }

    function initDOM() {
        $('select.js-change-url', gridParent).on('change', function(ev) {
            load( $(this).data('href').replace('-VALUE-', this.value) );
        });

        $('a.js-change-url', gridParent).on('click', function(ev) {
            load( this.href );
            return false;
        });

        $('thead select.js-sel', gridEl).on('change', function(ev) {
            var action = this.value;
            switch (action) {
                case 'show_all':
                    load(setUrlParam(config.grid_url, { selected:false }));
                    break;

                case 'show_sel':
                    load(setUrlParam(config.grid_url, { selected:'sel' }));
                    break;

                case 'show_unsel':
                    load(setUrlParam(config.grid_url, { selected:'unsel' }));
                    break;

                case 'upd_sel': case 'upd_unsel':
                    var sel = {};
                    $('tbody input.js-sel', gridEl).each(function(idx, el) {
                        $cb = $(this);
                        var key = $cb.parents('tr').data('id'), val = action === 'upd_sel';
                        sel[key] = val;
                    });
                    setSelection(sel);
                    break;
            }
            $(this).val('');
        });
        $('tbody input.js-sel', gridEl).on('change', function(ev) {
            $cb = $(this);
            var key = $cb.parents('tr').data('id'), val = $cb.prop('checked');
            setSelection({ key : val });
        })
    }
    initDOM();
}

$(function() {
    $('form').append($('<input type="hidden" name="X-CSRF-TOKEN"/>').val(csrfToken));
    $('.select2').select2({width:'other values', minimumResultsForSearch:20, dropdownAutoWidth:true});
})

