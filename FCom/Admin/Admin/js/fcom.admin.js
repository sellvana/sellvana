define(["jquery", "angular", "jquery-ui", "bootstrap", "fcom.core", 'ckeditor', 'jquery.bootstrap-growl', 'switch'], function ($, angular) {
    /*
     var myApp = angular.module("fcomApp", [], function($interpolateProvider) {
     $interpolateProvider.startSymbol("<%");
     $interpolateProvider.endSymbol("%>");
     });
     */
    FCom.Admin.Accordion = function (containerId, options) {
        var $container = $('#' + containerId);
        $container.find('.accordion-toggle').each(function (i, headingEl) {
            $(headingEl).attr('href', '#' + containerId + '-group' + i)
                .attr('data-toggle', 'collapse').attr('data-parent', '#' + containerId);
        });
        $container.find('.accordion-body').each(function (i, bodyEl) {
            $(bodyEl).attr('id', containerId + '-group' + i).addClass('collapse');
        });
    }

    FCom.Admin.Tabs = function (containerSel, options) {
        var $container = $(containerSel);
        $('.js-form-tab-toggle', $container).click(function (ev) {
            ev.preventDefault();
            var paneSel = ev.target.href.replace(/^[^#]*/, ''), pane = $(paneSel), tabId = paneSel.replace(/^#tab-/, '');

            if (options.url_get && !pane.data('loaded')) {
                var url_get = options.url_get + (options.url_get.match(/\?/) ? '&' : '?');
                $.getJSON(url_get + 'tabs=' + tabId, function (data, status, req) {
                    _.each(data.tabs, function (tabHtml, i) {
                        $('#tab-' + i).html(tabHtml).data('loaded', true);
                        $('#tab-' + i + ' .collapse').collapse();
                        if (options.tab_load_callback) {
                            options.tab_load_callback(i, tabHtml);
                        }
                    });
                });
            }
            $('#current_tab').val(ev.target.id.replace(/^tab-/, ''));
            $(this).tab('show');
        });
        if (options.cur_tab) {
            $('[href="#tab-' + options.cur_tab + '"]').tab('show');
        }
    }

    FCom.Admin.MediaLibrary = function (options) {
        var grid = $(options.grid || '#media-library'), container = grid.parents('.ui-jqgrid').parent();
        var baseUrl = options.url + '/download?folder=' + encodeURIComponent(options.folder) + '&file=';

        function setOptions(opt) {
            for (i in opt) {
                options[i] = opt[i];
            }
        }

        function editAttachment(ev) {
            var el = $(ev.target), tr = el.parents('tr'), rowid = tr.attr('id');
            el.hide('fast');
            $('.ui-icon-disk,.ui-icon-cancel', tr).show('fast');
            ev.stopPropagation();
            grid.jqGrid('editRow', rowid, {
                keys: true,
                oneditfunc: function () {
                    options.oneditfunc(tr);
                },
                successfunc: function (xhr) { /*console.log('successfunc');*/
                    editAttachmentRestore(tr);
                    return true;
                },
                errorfunc: function () { /*console.log('errorfunc');*/
                    el.show();
                },
                aftersavefunc: function () { /*console.log('aftersavefunc');*/
                    el.show();
                },
                afterrestorefunc: function () { /*console.log('afterrestorefunc');*/
                    editAttachmentRestore(tr);
                    return true;
                }
            });
            return false;
        }

        function editAttachmentSave(ev) {
            var el = $(ev.target), tr = el.parents('tr'), rowid = tr.attr('id');
            ev.stopPropagation();
            grid.jqGrid('saveRow', rowid);
            editAttachmentRestore(tr);
            return false;
        }

        function editAttachmentCancel(ev) {
            var el = $(ev.target), tr = el.parents('tr'), rowid = tr.attr('id');
            ev.stopPropagation();
            grid.jqGrid('restoreRow', rowid);
            editAttachmentRestore(tr);
            return false;
        }

        function editAttachmentRestore(tr) {
            $('.ui-icon-disk,.ui-icon-cancel', tr).hide('fast');
            $('.ui-icon-pencil', tr).show('fast');
        }

        function downloadAttachment(ev, inline) {
            var href = baseUrl + $(ev.target).data('file');
            ev.stopPropagation();
            if (!inline) {
                $('#upload-target', container)[0].contentWindow.location.href = href;
            } else {
                window.open(href + '&inline=1');
            }
            return false;
        }

        function getSelectedRows() {
            if (grid.jqGrid('getGridParam', 'multiselect')) {
                return grid.jqGrid('getGridParam', 'selarrrow');
            } else {
                var sel = grid.jqGrid('getGridParam', 'selrow');
                return sel === null ? [] : [sel];
            }
        }

        function deleteAttachments() {
            if (!confirm('Are you sure?')) {
                return false;
            }
            var sel = getSelectedRows(), i, postData = {'delete[]': []};
            if (!sel.length) {
                alert('Please select some attachments to delete.');
                return;
            }
            for (i = sel.length - 1; i >= 0; i--) {
                grid.jqGrid('setRowData', sel[i], {status: '...'});
                postData["delete[]"].push(grid.jqGrid('getRowData', sel[i]).file_name);
            }
            var url = options.url + '/delete?grid=' + grid.attr('id') + '&folder=' + encodeURIComponent(options.folder);
            $.post(url, postData, function (data, status, xhr) {
                for (i = sel.length - 1; i >= 0; i--) {
                    grid.jqGrid('delRowData', sel[i]);
                }
            });
        }

        function fmtActions(val, opt, obj) {
            if (!obj.status) {
                var file = $('<div/>').text(obj.file_name).html();
                html = '<span class=\"ui-icon ui-icon-pencil\" title=\"Edit\"></span>'
                    + '<span class=\"ui-icon ui-icon-disk\" style=\"display:none\" title=\"Save\"></span>'
                    + '<span class=\"ui-icon ui-icon-cancel\" style=\"display:none\" title=\"Cancel\"></span>'
                    + '<span class="ui-icon ui-icon-arrowthickstop-1-s" title="\Download\" data-file=\"' + file + '\"></span>'
                    + '<span class="ui-icon ui-icon-arrowreturnthick-1-e" title=\"Open\" data-file=\"' + file + '\"></span>';
            } else {
                html = obj.status;
            }
            return html;
        }

        $(grid).on('click', '.ui-icon-pencil', function (ev) {
            return editAttachment(ev);
        });
        $(grid).on('click', '.ui-icon-disk', function (ev) {
            return editAttachmentSave(ev);
        });
        $(grid).on('click', '.ui-icon-cancel', function (ev) {
            return editAttachmentCancel(ev);
        });
        $(grid).on('click', '.ui-icon-arrowthickstop-1-s', function (ev) {
            return downloadAttachment(ev)
        });
        $(grid).on('click', '.ui-icon-arrowreturnthick-1-e', function (ev) {
            return downloadAttachment(ev, true)
        });

        var colModel = grid[0].p.colModel;
        for (var i = 0; i < colModel.length; i++) {
            switch (colModel[i].name) {
                case 'file_size':
                    colModel[i].formatter = function (val, opt, obj) {
                        return Math.round(val / 1024) + 'k';
                    }
                    break;
                case 'act':
                    colModel[i].formatter = fmtActions;
                    break;
            }
        }
        //grid.trigger('reloadGrid');

        $('#upload-btn', container).unbind('click').find('.ui-pg-div').css({overflow: 'hidden'}).prepend($('<input type="file" name="upload[]" id="upload-input" value="Upload Media" multiple style="position:absolute; z-index:999; top:0; left:0; margin:-1px; padding:0; opacity:0">'));
        $(container).append('<iframe id="upload-target" name="upload-target" src="" style="width:0;height:0;border:0"></iframe>');

        $('#upload-input', container).change(function (ev) {
            console.log(this.files);
            var form = $(this).parents('form'), action = form.attr('action'), i, file;
            for (i = 0; i < this.files.length; i++) {
                file = this.files[i];
                console.log(file);
                grid.jqGrid('addRowData', file.fileName, {file_name: file.fileName, file_size: file.fileSize, status: '...'});
            }
            form.attr('action', options.url + '/upload?grid=' + grid.attr('id') + '&folder=' + encodeURIComponent(options.folder))
                .attr('target', 'upload-target')
                .attr('enctype', 'multipart/form-data')
                .submit();
            setTimeout(function () {
                form.attr('target', '').attr('enctype', '').attr('action', action);
            }, 100);
        });
        grid.parents('.ui-jqgrid').find('.navtable .ui-icon-trash').parents('.ui-pg-button').click(function (ev) {
            deleteAttachments();
        });

        return {
            setOptions: setOptions,
            getSelectedRows: getSelectedRows
        };
    }

    FCom.Admin.TargetGrid = function (options) {
        var source = $(options.source), target = $(options.target);
        var id = options.id || target.attr('id');
        var addInput = $('<input type="hidden" name="grid[' + id + '][add]" value=""/>');
        var delInput = $('<input type="hidden" name="grid[' + id + '][del]" value=""/>');
        target.parents('.ui-jqgrid').append(addInput, delInput);

        function addRows() {
            var sel = source.jqGrid('getGridParam', 'selarrrow'), data = [], i;
            var targetData = target.jqGrid('getRowData'), existingIds = {};
            for (i = 0; i < targetData.length; i++) {
                existingIds[targetData[i].id] = 1;
            }
            if (!sel.length) {
                alert('Please select some rows on the right to add.');
                return;
            }
            updateProducts('add', sel);
            for (i = 0; i < sel.length; i++) {
                if (!existingIds[sel[i]]) {
                    data.push(source.jqGrid('getRowData', sel[i]));
                }
            }
            for (i = sel.length - 1; i >= 0; i--) {
                source.jqGrid('setSelection', sel[i], false);
            }
            target.jqGrid('addRowData', 'id', data);
            target.trigger('reloadGrid');
        }

        function removeRows() {
            var sel = target.jqGrid('getGridParam', 'selarrrow'), i;
            if (!sel.length) {
                alert('Please select some rows to remove.');
                return;
            }
            updateProducts('remove', sel);
            for (i = sel.length - 1; i >= 0; i--) {
                target.jqGrid('delRowData', sel[i]);
            }
            target.trigger('reloadGrid');
        }

        function updateProducts(action, sel) {
            target = $(target);
            var container = target.parents('.ui-jqgrid').parent();
            var groupId = target.attr('id').replace(/.*?([0-9-]+)$/, '\$1'), i, idx, prodId;
            var fromEl = action === 'add' ? delInput : addInput,
                toEl = action === 'add' ? addInput : delInput;
            var fromData = fromEl.val().split(','), toData = toEl.val().split(',');
            for (i = 0; i < sel.length; i++) {
                if ((idx = $.inArray(sel[i], fromData)) != -1) {
                    fromData = fromData.splice(idx, 1);
                } else if ($.inArray(sel[i], toData) == -1) {
                    toData.push(sel[i]);
                }
            }
            fromEl.val(fromData.join(','));
            toEl.val(toData.join(','));
        }

        var toolbar = target.parents('.ui-jqgrid').find('.navtable');
        toolbar.find('.ui-icon-plus').parents('.ui-pg-button').click(addRows);
        toolbar.find('.ui-icon-trash').parents('.ui-pg-button').click(removeRows);

        return {}
    }

    FCom.Admin.load = function (collection, el) {
        el = $(el);
        var uid = el.data('uid');
        return FCom.Admin[collection][uid];
    }

    FCom.Admin.save = function (collection, el, object) {
        var uid = Math.random();
        el.data('uid', uid);
        FCom.Admin[collection][uid] = object || el;
    }

    FCom.Admin.checkboxButton = function (id, opt) {
        var el = $(id);
        var cur = opt[opt.def ? 'on' : 'off'];
        var label = $('<label for="' + id.replace(/^#/, '') + '">' + (opt.text ? $(cur.label).html() : '') + '</label>');
        el.css({display: 'none'}).after(label);
        label.css({display: 'inline-block'}).attr('title', cur.label).addClass(cur.icon);
        el.attr('checked', opt.def ? true : false)
            /*.button({text:!!cur.label, label:cur.label, icons: { primary:'ui-icon-'+cur.icon }})*/
            .click(function (ev) {
                label.removeClass(cur.icon);
                cur = opt[this.checked ? 'on' : 'off'];
                label.addClass(cur.icon);
                label.attr('title', cur.label);
                //el.button('option', {text:!!cur.label, label:cur.label, icons: { primary:'ui-icon-'+cur.icon }})
                if (opt.click) opt.click.bind(this)(ev);
            });
        return el;
    }

    FCom.Admin.buttonsetTabs = function (id, opt) {
        var el = $(id), id1 = id.replace(/^#/, ''), pane;
        opt = opt || {}
        $('input:radio', el).each(function (i, r) {
            if (!r.name) r.name = id1;
            if (!r.id) r.id = id1 + '-' + r.value;
            $(r).after('<label for="' + r.id + '">' + r.title + '</label>');
            if (opt.def && opt.def != r.value || !opt.def && i == 0) {
                pane = $('#' + r.value);
                r.checked = true;
            } else {
                $('#' + r.value).hide();
            }
            $(r).click(function (ev) {
                pane.hide();
                pane = $('#' + r.value).show();
            });
        });
        el.buttonset(el);
        return el;
    }

    FCom.Admin.ajaxCacheStorage = {}
    FCom.Admin.ajaxCache = function (url, callback) {
        if (callback === null) {
            delete FCom.Admin.ajaxCacheStorage[url];
        } else if (!FCom.Admin.ajaxCacheStorage[url]) {
            $.ajax(url, {dataType: 'json', success: function (data) {
                if (data._eval) {
                    var path, node, parent, idx, pathArr, i;
                    for (path in data._eval) {
                        node = data;
                        pathArr = path.split('/');
                        for (i = 0; i < pathArr.length; i++) {
                            parent = node;
                            idx = pathArr[i];
                            node = node[idx];
                            if (!node) break;
                        }
                        if (node) {
                            parent[idx] = eval(node);
                        }
                    }
                }
                FCom.Admin.ajaxCacheStorage[url] = data;
                callback(data);
            }});
        } else {
            callback(FCom.Admin.ajaxCacheStorage[url]);
        }
    }

    FCom.Admin.layouts = {}
    FCom.Admin.layout = function (id, opt) {
        var el = $(id);
        if (!opt) return FCom.Admin.load('layouts', el);

        if (opt.pub && opt.pub.resize) {
            for (var i in opt.pub.resize) {
                if (!opt.layout[i]) opt.layout[i] = {};
                opt.layout[i].onresize = function (name, el, state, opts, layout_name) {
                    $.publish('layouts/' + id + '/' + i + '/resize', [state.innerWidth, state.innerHeight]);
                }
            }
        }

        var layout = el.layout(opt.layout);
        FCom.Admin.save('layouts', el, layout);

        if (opt.pub && opt.pub.resize) {
            for (var i in opt.pub.resize) {
                $.publish('layouts/' + id + '/' + i + '/resize', [layout.state.center.innerWidth, layout.state.center.innerHeight]);
            }
        }
        if (opt.root) {
            function resize() {
                el.height($(window).height() - opt.root.margin);
            }

            resize();
            $(window).resize(resize);
        }
        return layout;
    }

    FCom.Admin.trees = {}
    FCom.Admin.tree = function (el, opt) {
        var expanded = false, el = $(el);

        if (!opt) return FCom.Admin.load('trees', el);

        function checkLock()
        {
            if (opt.lock_flag && $(opt.lock_flag).get(0).checked) {
                alert('Locked');
                return false;
            }
            return true;
        }

        function checkRoot(node, errorText)
        {
            if (parseInt($(node).attr('id')) <= 1) {
                alert(errorText);
                return false;
            }
            return true;
        }

        function bsModalHtml(id, title)
        {
            return '<div class="modal fade" id="' + id + '" tabindex="-1" role="dialog" aria-hidden="true">' +
                '   <div class="modal-dialog">' +
                '       <div class="modal-content">' +
                '           <div class="modal-header">' +
                '               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
                '               <h4 class="modal-title">' + title +'</h4>' +
                '           </div>' +
                '           <div class="modal-body"></div>' +
                '       </div>' +
                '   </div>' +
                '</div>';
        }

        function reorder(node) {
            console.log('node', node);
            if (!checkLock()) return;
            function postReorder(recursive) {
                $.post(opt.url, {
                    operation: 'reorderAZ',
                    id: $(node).attr("id").replace("node_", ""),
                    recursive: recursive ? 1 : ''
                }, function (data) {
                    if (!data.status) {
                        $.bootstrapGrowl("Error:<br>" + r.message, { type: 'danger', align: 'center', width: 'auto', delay: 5000});
                    } else {
                        el.jstree('refresh', node);
                    }
                });
            }

            //use boostraps modal box
            var reorderModalContent = bsModalHtml('jstree-reorder', 'Reorder A-Z');
            //trigger modal dialog
            $(reorderModalContent).modal({ backdrop: 'static', keyboard: true })
                .on('shown.bs.modal', function () {
                    $('<button></button>', {
                        type: 'button',
                        click: function () {
                            postReorder(false);
                        },
                        text: 'Only immediate children',
                        'data-dismiss': 'modal',
                        class: 'btn btn-default'
                    }).button().appendTo('#jstree-reorder .modal-body');
                    $('<button></button>', {
                        type: 'button',
                        click: function () {
                            postReorder(true);
                        },
                        text: 'All descendants',
                        'data-dismiss': 'modal',
                        class: 'btn btn-default'
                    }).button().appendTo('#jstree-reorder .modal-body');
                })
                .on('hidden.bs.modal', function () {
                    $(this).remove()
                });
        }

        function clone(node) {
            if (!checkLock()) return;
            if (!checkRoot(node, 'Cannot clone ROOT')) return;

            function postClone(recursive) {
                $.post(opt.url, {
                    operation: 'clone',
                    id: $(node).attr("id").replace("node_", ""),
                    recursive: recursive
                }, function(r){
                    if (!r.status) {
                        $.bootstrapGrowl("Error:<br>" + r.message, { type: 'danger', align: 'center', width: 'auto', delay: 5000});
                    } else {
                        el.jstree('refresh', $.jstree._focused()._get_parent());
                    }
                });
            }

            var reorderModalContent = bsModalHtml('jstree-clone', 'Clone');
            $(reorderModalContent).modal({ backdrop: 'static', keyboard: true })
                .on('shown.bs.modal', function () {
                    $('<button></button>', {
                        type: 'button',
                        click: function () {
                            postClone(0);
                        },
                        text: 'Clone only this node',
                        'data-dismiss': 'modal',
                        class: 'btn btn-default'
                    }).button().appendTo('#jstree-clone .modal-body');
                    $('<button></button>', {
                        type: 'button',
                        click: function () {
                            postClone(1);
                        },
                        text: 'Clone this node and immediately children',
                        'data-dismiss': 'modal',
                        class: 'btn btn-default'
                    }).button().appendTo('#jstree-clone .modal-body');
                    $('<button></button>', {
                        type: 'button',
                        click: function () {
                            postClone(2);
                        },
                        text: 'Clone this node and all descendant',
                        'data-dismiss': 'modal',
                        class: 'btn btn-default'
                    }).button().appendTo('#jstree-clone .modal-body');
                })
                .on('hidden.bs.modal', function () {
                    $(this).remove()
                });
        }

        var plugins = ["themes", "json_data", "ui", "crrm", "cookies", "dnd", "search", "types", "hotkeys", "contextmenu"];
        if (opt.checkbox) {
            plugins.push("checkbox");
        }

        el.jstree({
            'core': {
                animation: 0,
                'initially_open': opt.initially_open
            },
            "plugins": plugins,
            'themes': {
                //dots: false,
                //icons: false
            },
            "dnd" : {
                "copy_modifier" : true,
                "drag_check" : function (data) {
                    return {
                        after : true ,
                        before : true ,
                        inside : true
                    };
                }
            },
            "json_data": {
                "ajax": {"url": opt.url, "data": function (n) {
                    return {
                        "operation": "get_children",
                        "id": n.attr ? n.attr("id").replace("node_", "") : 'NULL',
                        'expanded': expanded ? 1 : '',
                        'refresh': n != -1 ? 1 : ''
                    };
                }
                }},
            // Configuring the search plugin
            "search": {
                "ajax": {"url": opt.url, "data": function (str) {
                    return { "operation": "search", "search_str": str};
                }
                }},
            "contextmenu": {
                'items': {
                    'ccp': {label: 'Clone', submenu: '', separator_before: true, action: function (n) {
                        el.trigger('clone.jstree', [n]);
                    }},
                    'select': {label: 'Select', separator_before: true, action: function (n) {
                        el.trigger('select.jstree', [n]);
                    }},
                    'reorder': {label: 'Reorder A-Z', separator_before: true, action: function (n) {
                        reorder(n);
                    }},
                    'refresh': {label: 'Refresh', separator_before: true, action: function (n) {
                        el.jstree('refresh', n);
                    }}

                }
            },
            'cookies': { auto_save: false, save_opened: false, save_selected: false},
            'checkbox': opt.checkbox/*{ real_checkboxes:true }*/
        })
            .bind("loaded.jstree", function (e, data) {
                if (opt.create_lock) {
                    $(el).find('li#1>a').after('<input class="entypo lock" id="' + opt.lock_flag.replace('#', '') + '" type="checkbox"/>');
                    FCom.Admin.checkboxButton(opt.lock_flag, {def: true,
                        on: {icon: 'icon-lock', label: 'Editing Locked'},
                        off: {icon: 'icon-unlock', label: 'Editing Unlocked'}
                    });
                }
            })
            .bind("before.jstree", function (e, data) {
                if (data.func.match(/(create|remove|rename|move_node)/) && !checkLock()) {
                    e.stopImmediatePropagation();
                    return false;
                }
                if (data.func == 'remove' && !confirm('Are you sure?')) {
                    e.stopImmediatePropagation();
                    return false;
                }
            })
            .bind("create.jstree", function (e, data) {
                $.post(opt.url, {
                        "operation": "create_node",
                        "id": data.rslt.parent.attr("id").replace("node_", ""),
                        "position": data.rslt.position,
                        "title": data.rslt.name,
                        "type": data.rslt.obj.attr("rel")
                    }, function (r) {
                        if (r.status) {
                            $(data.rslt.obj).attr("id", "node_" + r.id);
                            var nodeSelected = $.jstree._focused().get_selected();
                            el.jstree('deselect_node').trigger('deselect_node.jstree', nodeSelected);
                            el.jstree('select_node', data.rslt.obj);
                        }
                        else {
                            $.bootstrapGrowl("Error:<br>" + r.message, { type: 'danger', align: 'center', width: 'auto', delay: 5000});
//                            $.jstree.rollback(data.rlbk);
                            data.rslt.obj.remove();
                        }
                    }
                );
            })
            .bind("remove.jstree", function (e, data) {
                data.rslt.obj.each(function () {
                    $.ajax({
                        async: false,
                        type: 'POST',
                        url: opt.url,
                        data: {
                            "operation": "remove_node",
                            "id": this.id.replace("node_", "")
                        },
                        success: function (r) {
                            if (!r.status) {
                                alert(r.message);
                                data.inst.refresh();
                            }
                        }
                    });
                });
            })
            .bind("rename.jstree", function (e, data) {
                data.rslt.obj.children("a").addClass("jstree-loading");
                $.post(opt.url, {
                        "operation": "rename_node",
                        "id": data.rslt.obj.attr("id").replace("node_", ""),
                        "title": data.rslt.new_name
                    }, function (r) {
                        if (!r.status) {
                            $.bootstrapGrowl("Error:<br>" + r.message, { type: 'danger', align: 'center', width: 'auto', delay: 5000});
                            el.jstree('refresh', $.jstree._focused()._get_parent(data.rslt.obj) );
                        } else {
                            el.trigger('select.jstree', data.rslt.obj);
                        }
                    }
                );
            })
            .bind("move_node.jstree", function (e, data) {
                data.rslt.o.each(function (i) {
                    $.ajax({async: false, type: 'POST', url: opt.url,
                        data: {
                            "operation": "move_node",
                            "id": $(this).attr("id").replace("node_", ""),
                            "ref": data.rslt.cr === -1 ? 1 : data.rslt.np.attr("id").replace("node_", ""),
                            "position": data.rslt.cp + i,
                            "title": data.rslt.name,
                            "copy": data.rslt.cy ? 1 : 0
                        },
                        success: function (r) {
                            if (!r.status) {
                                $.bootstrapGrowl("Error:<br>" + r.message, { type: 'danger', align: 'center', width: 'auto', delay: 5000});
                                $.jstree.rollback(data.rlbk);
                            }
                            else {
                                $(data.rslt.oc).attr("id", "node_" + r.id);
                                if (data.rslt.cy && $(data.rslt.oc).children("UL").length) {
                                    data.inst.refresh(data.inst._get_parent(data.rslt.oc));
                                }
                            }
                            $("#analyze").click();
                        }
                    });
                });
            })
            .bind("select_node.jstree", function (e, data) {
                if (typeof opt.on_click !== 'undefined') opt.on_click(data.rslt.obj);
            })
            .bind("dblclick.jstree", function (ev) {
                var node = $(ev.target).closest('li');
                if (typeof opt.on_dblclick !== 'undefined') opt.on_dblclick(node);
            })
            .bind("select.jstree", function (ev, node) {
                if (typeof opt.on_select !== 'undefined') opt.on_select(node);
            })
            .bind("reorder.jstree", function(ev, node) {
                reorder(node);
            })
            .bind("clone.jstree", function (e, node) {
                clone(node);
            })
            /*    .bind("check_node.jstree", function (e, data) {
                    data.rslt.obj.each(function () {
                        $.ajax({
                            async : false,
                            type: 'POST',
                            url: opt.url,
                            data : {
                                "operation" : "check_node",
                                "id" : this.id.replace("node_","")
                            },
                            success : function (r) {
                                if(!r.status) {
                                    alert(r.message);
                                    data.inst.refresh();
                                }
                            }
                        });
                    });
                })
                .bind("uncheck_node.jstree", function (e, data) {
                    data.rslt.obj.each(function () {
                        $.ajax({
                            async : false,
                            type: 'POST',
                            url: opt.url,
                            data : {
                                "operation" : "uncheck_node",
                                "id" : this.id.replace("node_","")
                            },
                            success : function (r) {
                                if(!r.status) {
                                    alert(r.message);
                                    data.inst.refresh();
                                }
                            }
                        });
                    });
                })*/
        ;

        $(el).on('drop', 'a', function (e, dd) {
            if (!$(e.target).parents('.jstree').length || e.alreadyProcessed) {
                return;
            }
            e.alreadyProcessed = true;

            if (dd.grid) {
                var copy = e.ctrlKey, rowIDs = [];
                if (!confirm('Are you sure you want to ' + (copy ? 'copy' : 'move') + ' ' + dd.count + ' row(s) to ' + e.target.innerText)) {
                    return;
                }
                console.log(dd, $(dd.drag).parents('.grid-container'));
                for (var i = 0; i < dd.rows.length; i++) {
                    rowIDs.push(dd.grid.getDataItem(dd.rows[i]).id);
                }
                $.post(opt.url, {
                        "operation": "associate." + $(dd.drag).parents('.grid-container').attr('rel'),
                        "id": $(this).parent().attr('id').replace('node_', ''),
                        'ref': rowIDs,
                        'copy': copy
                    },
                    function (r) {
                        console.log(r);
                    }
                );

                dd.grid.invalidate();
                dd.grid.setSelectedRows([]);
            }
        });

        FCom.Admin.save('trees', el);

        function toggleExpand() {
            $('#1 li', el).each(function (idx, li) {
                console.log(idx, li);
                el.jstree('toggle_node', li);
            });
        }

        return {toggleExpand: toggleExpand};
    }

    FCom.Admin.forms = {}
    FCom.Admin.form = function (options) {
        /* options = {
            tabs:'.adm-tabs-left li',
            panes:'.adm-tabs-content',
            url_get: '.../form_tab/:id',
            url_post: '.../edit/:id'
        } */
        var tabs, panes, curLi, curPane, editors = {};

        function loadTabs(data) {
            for (var i in data.tabs) {
                $('#tab-' + i).html(data.tabs[i]).data('loaded', true);
                if (typeof options.on_tab_load !== 'undefined') {
                    options.on_tab_load.apply($('#tab-' + i));
                }
            }
        }

        function wysiwygCreate(id) {
            if (!editors[id] && CKEDITOR !== 'undefined' && !CKEDITOR.instances[id]) {
                console.log(id, 'wysiwygcreate');
                editors[id] = true; // prevent double loading
                CKEDITOR.replace(id, {
                    /*toolbarGroups: [
                        { name: 'mode' },
                        { name: 'basicstyles' },
                        { name: 'links' }
                    ],*/
                    startupMode: 'wysiwyg'
                });
//
//                $('#'+id).ckeditor(function() {
//                    this.dataProcessor.writer.indentationChars = '  ';
//                    editors[id] = this;
//                });
            }
        }

        // this function almost use to init ckeditor after load ajax form
        function wysiwygInit() {
            var form = this;
            $('textarea.ckeditor').each(function () {
                var id = $(this).attr('id');
                if (!id) {
                    var cntEditors = editors.length;
                    id = cntEditors + 1;
                    $(this).attr('id', 'textarea-ckeditor-' + id);
                }
                form.wysiwygCreate(id);
            });
        }

        function wysiwygDestroy(id) {
            if (editors[id]) {
                try {
                    editors[id].destroy();
                } catch (e) {
                    editors[id].destroy();
                }
                editors[id] = null;
            }
        }

        function createSwitchButton() { //todo: add options class to add switch button
            $('.switch-cbx').each(function () {
                if ($(this).parents('.make-switch').hasClass('make-switch') == false) {
                    $(this).wrap("<div class='make-switch switch' data-off-label='&lt;i class=\"icon-remove\"&gt;&lt;/i&gt;' data-on-label='&lt;i class=\"icon-ok\"&gt;&lt;/i&gt;' data-on='primary'>").parent().bootstrapSwitch();
                }
            });
        }

        function tabClass(id, cls) {
            var tab = $('a[href=#tab-' + id + ']', tabs).parent('li');
            tab.removeClass('dirty error');
            if (cls) tab.addClass(cls);
        }

        function tabAction(action, el) {
            var pane = $(el).parents(options.panes);
            var tabId = pane.attr('id').replace(/^tab-/, '');
            var url_get = options.url_get + (options.url_get.match(/\?/) ? '&' : '?');
            var url_post = options.url_get + (options.url_post.match(/\?/) ? '&' : '?');
            switch (action) {
                case 'edit':
                    $.get(url_get + 'tabs=' + tabId + '&mode=edit', function (data, status, req) {
                        loadTabs(data);
                        tabClass(tabId, 'dirty');
                    });
                    break;

                case 'cancel':
                    $.get(url_get + 'tabs=' + tabId + '&mode=view', function (data, status, req) {
                        loadTabs(data);
                        tabClass(tabId);
                    });
                    break;

                case 'save':
                    for (var i in editors) {
                        editors[i].updateElement();
                    }
                    var postData = $(el).parents('fieldset').find('input,select,textarea').serializeArray();
                    $.post(url_post + 'tabs=' + tabId + '&mode=view', postData, function (data, status, req) {
                        loadTabs(data);
                        tabClass(tabId);
                    });
                    break;

                case 'dirty':
                    $('a[href=#' + tabId + ']', tabs).addClass('changed');
                    break;

                case 'clean':
                    $('a[href=#' + tabId + ']', tabs).removelass('changed');
                    break;
            }
            return false;
        }

        function saveAll(el) {
            return true;
            //TODO
            var form = $(el).closest('form');
            var postData = form.serializeArray();
            var url_post = options.url_get + (options.url_post.match(/\?/) ? '&' : '?');
            $.post(url_post + 'tabs=ALL&mode=view', postData, function (data, status, req) {
                console.log(data);
                $.pnotify({
                    pnotify_title: data.message || 'The form has been saved',
                    pnotify_type: data.status == 'error' ? 'error' : null,
                    pnotify_history: false,
                    pnotify_nonblock: true, pnotify_nonblock_opacity: .3
                });
                loadTabs(data);
                for (var i in data.tabs) {
                    tabClass(i);
                }
            });
            return false;
        }

        function deleteForm(el) {
            if (!confirm('Are you sure?')) return false;
            var form = $(el).parents('form');
            $('input[name=_delete]', form).val(1);
            return true;
        }

        function setOptions(newOpt) {
            $.extend(options, newOpt);
            return this;
        }

        $(function () {
            $.fn.validate && $(options.panes).closest('form').validate(options.validate || {});

            var tabs = $(options.tabs);
            var panes = $(options.panes);
            var curLi = $(options.tabs + '[class=active]');
            var curPane = $(options.panes + ':not([hidden])');

            $(panes).on('change', 'input,textarea,select', function (ev) {
                var tabId = $(ev.target).closest(options.panes).attr('id');
                $('a[href=#' + tabId + ']', tabs).closest('li').addClass('dirty');
            });
            $('a', tabs).click(function (ev) {
                var a = $(ev.currentTarget), li = a.parent('li');
                if (a.data('no-tab')) {
                    return;
                }
                curLi.removeClass('active');
                curPane.attr('hidden', 'hidden');
                ev.stopPropagation();

                if (curLi === li) {
                    return false;
                }
                var pane = $(a.attr('href'));
                li.addClass('active');
                pane.removeAttr('hidden');
                curLi = li;
                curPane = pane;
                var tabId = a.attr('href').replace(/^#tab-/, '');
                pane.closest('form').find('#tab').val(tabId);
                if (!pane.data('loaded')) {
                    var url_get = options.url_get + (options.url_get.match(/\?/) ? '&' : '?');
                    $.getJSON(url_get + 'tabs=' + tabId, function (data, status, req) {
                        loadTabs(data);
                    });
                }
                return false;
            });
        });

        return {
            setOptions: setOptions,
            loadTabs: loadTabs,
            wysiwygInit: wysiwygInit,
            wysiwygCreate: wysiwygCreate,
            wysiwygDestroy: wysiwygDestroy,
            createSwitchButton: createSwitchButton,
            tabClass: tabClass,
            tabAction: tabAction,
            saveAll: saveAll,
            deleteForm: deleteForm
        };
    }

    if ($.jgrid) {
        $.extend($.jgrid.defaults, {

        });

        $.extend($.jgrid.edit, {

        });

        $.extend($.jgrid.add, {

        });

        $.extend($.jgrid.del, {

        });

        $.extend($.jgrid.view, {

        });

        $.extend($.jgrid.search, {

        });

        $.extend($.jgrid.nav, {

        });
    }

    FCom.Admin.jqGrid = {}

    FCom.Admin.jqGrid.fmtHiddenInput = function (cellvalue, options, rowObject) {
        console.log(cellvalue, options, rowObject);
        // do something here
        return cellvalue ? cellvalue : '';
    }

    FCom.Admin.jqGrid.fmtNewWindow = function (val, opt, obj) {
        return "<a href='javascript:window.open(\"" + val + "\", \"vendor_website_url\", \"width=800,height=600\")'>" + val + "</a>";
    }

    FCom.Admin.jqGrid.fmtRadioButton = function (val, opt, obj) {
        var id = opt.colModel.inputId || opt.gid + '-' + opt.colModel.name + '-' + val;
        var name = opt.colModel.inputName || opt.gid + '[' + opt.colModel.name + ']';
        return '<input type="radio" id="' + id + '" name="' + name + '" value="' + val + '"/>';
    }

    $.widget('ui.fcom_autocomplete', {
        _create: function () {
            var self = this, input = this.element, field = $(this.options.field), value = field.val();
            var cache = {}, lastXhr;
            var options = $.extend({
                minLength: 0,
                source: function (request, response) {
                    var term = request.term;
                    if (term in cache) {
                        response(cache[term]);
                        return;
                    }
                    var url = self.options.url, query = $(self.options.filter).serialize();
                    if (query) {
                        url += (url.match(/\?/) ? '&' : '?') + query;
                    }
                    lastXhr = $.getJSON(url, request, function (data, status, xhr) {
                        cache[term] = data;
                        if (xhr === lastXhr) {
                            response(data);
                            /*response($.map( data, function( item ) {
                                return {
                                    //label: item.name,//item.name + (item.adminName1 ? ", " + item.adminName1 : "") + ", " + item.countryName,
                                    value: item.name,
                                    id: item.id
                                }
                            }));*/
                        }
                    });
                },
                select: function (event, ui) {
                    field.val(ui.item.id);
                    self.options.select && self.options.select(event, ui);
                },
                change: function (event, ui) {
                    /*
                    if (self.options.exact && !ui.item ) {
                        $(this).val('');
                        field.val('');
                        return false;
                    }
                    */
                    self.options.change && self.options.change(event, ui);
                }
            }, this.options.widget || {});
            input.autocomplete(options).focus(function (ev) {
                if (!$(this).val()) {
                    $(this).autocomplete('search', '');
                }
            });
        },

        destroy: function () {
            this.input.remove();
            this.button.remove();
            this.element.show();
            $.Widget.prototype.destroy.call(this);
        }
    });

    FCom.Admin.codeEditorThemeLoaded = {};

    FCom.Admin.initCodeEditors = function () {
        if (typeof CodeMirror == 'undefined') return;

        var scriptBaseUrl = FCom.Admin.codemirrorBaseUrl;

        CodeMirror.modeURL = scriptBaseUrl + '/mode/%N/%N.js';

        $('.js-code-editor').each(function (idx, el) {
            var $el = $(el);
            if ($el.data('editor')) return;

            var theme = $el.data('theme') || 'monokai';
            var mode = $el.data('mode');

            if (!FCom.Admin.codeEditorThemeLoaded[theme]) {
                var ss = scriptBaseUrl + '/theme/' + theme + '.css';
                if (document.createStyleSheet) {
                    document.createStyleSheet(ss);
                } else {
                    $('<link rel="stylesheet" type="text/css" media="screen" href="' + ss + '"/>').appendTo('head');
                }
                FCom.Admin.codeEditorThemeLoaded[theme] = true;
            }

            var options = { lineNumbers: true, mode: mode, theme: theme };
            editor = CodeMirror.fromTextArea(el, options);
            if (mode) {
                //editor.setOption('mode', mode);
                CodeMirror.autoLoadMode(editor, mode);
            }
            $(el).data('editor', editor);
        });
    }

    $.fn.resizeWithWindow = function (options) {
        var settings = $.extend({ x: false, y: true, dX: null, dX: null, initBy: null, jqGrid: null }, options || {});
        var $win = $(window), $el = this, isGrid = settings.jqGrid || $el.hasClass('ui-jqgrid-btable');

        function resize() {
            if (settings.initBy) {
                var w = $by.outerWidth() - padX, h = $by.outerHeight() - padY;
            } else {
                var c = $el.offset(), w = $win.width() - dX - (c.left - coords.left), h = $win.height() - dY - (c.top - coords.top);
            }
            if (isGrid) {
                if (settings.x) $el.jqGrid('setGridWidth', w);
                if (settings.y) $el.jqGrid('setGridHeight', h);
            } else {
                if (settings.x) $el.width(w);
                if (settings.y) $el.height(h);
            }
        }

        if (settings.initBy) {
            var $by = $(settings.initBy), parents = $el.parents(), padX = 0, padY = 0, isParent = false;
            for (var i = 0, ii = parents.length; i < ii; i++) {
                var $p = $(parents[i]);
                if (settings.x) padX += $p.outerWidth(true) - $p.width();
                if (settings.y) padY += $p.outerHeight(true) - $p.height();
                if (parents[i] === $by[0]) {
                    isParent = true;
                    break;
                }
            }
            if (isParent) {
                if (settings.x) $el.width($by.outerWidth() - padX);
                if (settings.y) $el.height($by.outerHeight() - padY);
            }
        } else {
            var dX = settings.dX !== null ? settings.dX : $win.width() - $el.width(),
                dY = settings.dY !== null ? settings.dY : $win.height() - $el.height(),
                coords = $el.offset();
        }

        resize();
        $win.resize(resize);
    }

    $(function () {
        if ($.jgrid) {
            $.jgrid.formatter.date.newformat = 'm/d/Y';
            $.jgrid.edit.width = 500;
        }

        if (typeof CKEDITOR !== 'undefined') {
            CKEDITOR.config.autoUpdateElement = true;
            CKEDITOR.config.toolbarCanCollapse = true;
            CKEDITOR.config.toolbarStartupExpanded = false;
            CKEDITOR.config.startupMode = 'wysiwyg';//'source';
            //CKEDITOR.config.filebrowserUploadUrl = '/';
        }
        //$('.datepicker').datepicker();
        $(document).bind('ajaxSuccess', function (event, request, settings) {
            if (request.responseText[0]==='{' && (data = $.parseJSON(request.responseText))) {
                if (data.error == 'login') {
                    location.href = FCom.base_href;
                }
            }
        });
        $('header .navbar .toggle-nav').click(function (ev) {
            var postData = { do: 'nav.collapse', collapsed: $('body').hasClass('main-nav-closed') ? 1 : 0 };
            $.post(FCom.Admin.personalize_href, postData, function (response, status, xhr) {
                console.log(response);
            });
        })
        $('.nav-group header').click(function (ev) {
            $(ev.currentTarget).parent('li').find('ul').animate({
                opacity: 'toggle',
                height: 'toggle'
            }, 100);
        });

        FCom.Admin.initCodeEditors();

        $('.js-resizable').each(function (idx, el) {
            $(el).resizable();
        })

        $.fn.foundationCustomForms && $(".foundation-forms").foundationCustomForms();
    })

    angular.module('fcom.directives', [])

        .directive('fcomSelect2', function () {
            return {
                restrict: 'AC',
                link: function ($scope, element, attrs) {
                    var params = {
                        width: 'other values', minimumResultsForSearch: 20
                    }
                    angular.extend(params, $scope.$eval(attrs.mySelect2));

                    $(element).select2(params || {});
                }
            }
        })
})
