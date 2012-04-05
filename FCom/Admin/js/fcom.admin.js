
var FCom_Admin = {};

FCom_Admin.MediaLibrary = function(options) {
    var grid = $(options.grid || '#media-library'), container = grid.parents('.ui-jqgrid').parent();
    var baseUrl = options.url+'/download?folder='+encodeURI(options.folder)+'&file=';

    function setOptions(opt) {
        for (i in opt) {
            options[i] = opt[i];
        }
    }

    function editAttachment(ev) {
        var el = $(ev.target), tr = el.parents('tr'), rowid = tr.attr('id');
        el.hide('fast'); $('.ui-icon-disk,.ui-icon-cancel', tr).show('fast');
        ev.stopPropagation();
        grid.jqGrid('editRow', rowid, {
            keys:true,
            oneditfunc:function() { options.oneditfunc(tr); },
            successfunc:function(xhr) { /*console.log('successfunc');*/
                editAttachmentRestore(tr);
                return true;
            },
            errorfunc:function() { /*console.log('errorfunc');*/ el.show();  },
            aftersavefunc:function() { /*console.log('aftersavefunc');*/ el.show(); },
            afterrestorefunc:function() { /*console.log('afterrestorefunc');*/
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
        $('.ui-icon-disk,.ui-icon-cancel', tr).hide('fast'); $('.ui-icon-pencil', tr).show('fast');
    }

    function downloadAttachment(ev, inline) {
        var href = baseUrl+$(ev.target).data('file');
        ev.stopPropagation();
        if (!inline) {
            $('#upload-target', container)[0].contentWindow.location.href = href;
        } else {
            window.open(href+'&inline=1');
        }
        return false;
    }

    function deleteAttachments() {
        if (!confirm('Are you sure?')) {
            return false;
        }
        var sel = grid.jqGrid('getGridParam', 'selarrrow'), i, postData = {'delete[]':[]};
        if (!sel.length) {
            alert('Please select some attachments to delete.');
            return;
        }
        for (i=sel.length-1; i>=0; i--) {
            grid.jqGrid('setRowData', sel[i], {status:'...'});
            postData["delete[]"].push(grid.jqGrid('getRowData', sel[i]).file_name);
        }
        $.post(options.url+'/delete?grid='+grid.attr('id')+'&folder='+encodeURI(options.folder), postData, function(data, status, xhr) {
            for (i=sel.length-1; i>=0; i--) {
                grid.jqGrid('delRowData', sel[i]);
            }
        });
    }

    function fmtActions(val,opt,obj) {
        if (!obj.status) {
            var file = $('<div/>').text(obj.file_name).html();
            html = '<span class=\"ui-icon ui-icon-pencil\" title=\"Edit\"></span>'
                +'<span class=\"ui-icon ui-icon-disk\" style=\"display:none\" title=\"Save\"></span>'
                +'<span class=\"ui-icon ui-icon-cancel\" style=\"display:none\" title=\"Cancel\"></span>'
                +'<span class="ui-icon ui-icon-arrowthickstop-1-s" title="\Download\" data-file=\"'+file+'\"></span>'
                +'<span class="ui-icon ui-icon-arrowreturnthick-1-e" title=\"Open\" data-file=\"'+file+'\"></span>';
        } else {
            html = obj.status;
        }
        return html;
    }

    $('.ui-icon-pencil', grid).live('click', function(ev) { return editAttachment(ev); });
    $('.ui-icon-disk', grid).live('click', function(ev) { return editAttachmentSave(ev); });
    $('.ui-icon-cancel', grid).live('click', function(ev) { return editAttachmentCancel(ev); });
    $('.ui-icon-arrowthickstop-1-s', grid).live('click', function(ev) { return downloadAttachment(ev) });
    $('.ui-icon-arrowreturnthick-1-e', grid).live('click', function(ev) { return downloadAttachment(ev, true) });

    var colModel = grid[0].p.colModel;
    for (var i=0; i<colModel.length; i++) {
        switch (colModel[i].name) {
            case 'file_size':
                colModel[i].formatter = function(val,opt,obj){ return Math.round(val/1024)+'k'; }
                break;
            case 'act':
                colModel[i].formatter = fmtActions;
                break;
        }
    }
    //grid.trigger('reloadGrid');

    $('#upload-btn', container).unbind('click').find('.ui-pg-div').css({overflow:'hidden'}).prepend($('<input type="file" name="upload[]" id="upload-input" value="Upload Media" multiple style="position:absolute; z-index:999; top:0; left:0; margin:-1px; padding:0; opacity:0">'));
    $(container).append('<iframe id="upload-target" name="upload-target" src="" style="width:0;height:0;border:0"></iframe>');

    $('#upload-input', container).change(function(ev) {
        console.log(this.files);
        var form = $(this).parents('form'), action = form.attr('action'), i, file;
        for (i=0; i<this.files.length; i++) {
            file = this.files[i];
    console.log(file);
            grid.jqGrid('addRowData', file.fileName, {file_name:file.fileName, file_size:file.fileSize, status:'...'});
        }
        form.attr('action', options.url+'/upload?grid='+grid.attr('id')+'&folder='+encodeURI(options.folder))
            .attr('target', 'upload-target')
            .attr('enctype', 'multipart/form-data')
            .submit();
        setTimeout(function() { form.attr('target', '').attr('enctype', '').attr('action', action); }, 100);
    });
    grid.parents('.ui-jqgrid').find('.navtable .ui-icon-trash').parents('.ui-pg-button').click(function(ev) { deleteAttachments(); });

    return {setOptions:setOptions};
}

FCom_Admin.TargetGrid = function(options) {
    var source = $(options.source), target = $(options.target);
    var id = options.id || target.attr('id');
    var addInput = $('<input type="hidden" name="grid['+id+'][add]" value=""/>');
    var delInput = $('<input type="hidden" name="grid['+id+'][del]" value=""/>');
    target.parents('.ui-jqgrid').append(addInput, delInput);

    function addRows() {
        var sel = source.jqGrid('getGridParam', 'selarrrow'), data = [], i;
        var targetData = target.jqGrid('getRowData'), existingIds = {};
        for (i=0; i<targetData.length; i++) {
            existingIds[targetData[i].id] = 1;
        }
        if (!sel.length) {
            alert('Please select some rows on the right to add.');
            return;
        }
        updateProducts('add', sel);
        for (i=0; i<sel.length; i++) {
            if (!existingIds[sel[i]]) {
                data.push(source.jqGrid('getRowData', sel[i]));
            }
        }
        for (i=sel.length-1; i>=0; i--) {
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
        for (i=sel.length-1; i>=0; i--) {
            target.jqGrid('delRowData', sel[i]);
        }
        target.trigger('reloadGrid');
    }

    function updateProducts(action, sel) {
        target = $(target);
        var container = target.parents('.ui-jqgrid').parent();
        var groupId = target.attr('id').replace(/.*?([0-9-]+)$/, '\$1'), i, idx, prodId;
        var fromEl = action==='add' ? delInput : addInput,
            toEl = action==='add' ? addInput : delInput;
        var fromData = fromEl.val().split(','), toData = toEl.val().split(',');
        for (i=0; i<sel.length; i++) {
            if ((idx = $.inArray(sel[i], fromData))!=-1) {
                fromData = fromData.splice(idx, 1);
            } else if ($.inArray(sel[i], toData)==-1) {
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

var Admin = {
    load: function(collection, el) {
        el = $(el);
        var uid = el.data('uid');
        return Admin[collection][uid];
    }
    ,save: function(collection, el, object) {
        var uid = Math.random();
        el.data('uid', uid);
        Admin[collection][uid] = object || el;
    }

    ,checkboxButton: function(id, opt) {
        var el = $(id);
        var cur = opt[opt.def ? 'on' : 'off'];
        el.after('<label for="'+id.replace(/^#/,'')+'">'+(opt.text ? $(cur.label).html() : '')+'</label>');
        el.attr('checked', opt.def ? true : false)
            .button({text:!!cur.label, label:cur.label, icons: { primary:'ui-icon-'+cur.icon }})
            .click(function(ev) {
                cur = opt[this.checked ? 'on' : 'off'];
                el.button('option', {text:!!cur.label, label:cur.label, icons: { primary:'ui-icon-'+cur.icon }})
                if (opt.click) opt.click.bind(this)(ev);
            });
        return el;
    }
    ,buttonsetTabs: function(id, opt) {
        var el = $(id), id1 = id.replace(/^#/, ''), pane;
        opt = opt || {}
        $('input:radio', el).each(function(i, r) {
            if (!r.name) r.name = id1;
            if (!r.id) r.id = id1+'-'+r.value;
            $(r).after('<label for="'+r.id+'">'+r.title+'</label>');
            if (opt.def && opt.def!=r.value || !opt.def && i==0) {
                pane = $('#'+r.value);
                r.checked = true;
            } else {
                $('#'+r.value).hide();
            }
            $(r).click(function(ev) { pane.hide(); pane = $('#'+r.value).show(); });
        });
        el.buttonset(el);
        return el;
    }

    ,ajaxCacheStorage: {}
    ,ajaxCache: function(url, callback) {
        if (callback===null) {
            delete Admin.ajaxCacheStorage[url];
        } else if (!Admin.ajaxCacheStorage[url]) {
            $.ajax(url, {dataType:'json', success:function(data) {
                if (data._eval) {
                    var path, node, parent, idx, pathArr, i;
                    for (path in data._eval) {
                        node = data;
                        pathArr = path.split('/');
                        for (i=0; i<pathArr.length; i++) {
                            parent = node; idx = pathArr[i]; node = node[idx];
                            if (!node) break;
                        }
                        if (node) {
                            parent[idx] = eval(node);
                        }
                    }
                }
                Admin.ajaxCacheStorage[url] = data;
                callback(data);
            }});
        } else {
            callback(Admin.ajaxCacheStorage[url]);
        }
    }

    ,layouts:{}
    ,layout: function(id, opt) {
        var el = $(id);
        if (!opt) return Admin.load('layouts', el);

        if (opt.pub && opt.pub.resize) {
            for (var i in opt.pub.resize) {
                if (!opt.layout[i]) opt.layout[i] = {};
                opt.layout[i].onresize = function (name, el, state, opts, layout_name) {
                    $.publish('layouts/'+id+'/'+i+'/resize', [state.innerWidth, state.innerHeight]);
                }
            }
        }

        var layout = el.layout(opt.layout);
        Admin.save('layouts', el, layout);

        if (opt.pub && opt.pub.resize) {
            for (var i in opt.pub.resize) {
                $.publish('layouts/'+id+'/'+i+'/resize', [layout.state.center.innerWidth, layout.state.center.innerHeight]);
            }
        }
        if (opt.root) {
            function resize() { el.height($(window).height()-opt.root.margin); }
            resize(); $(window).resize(resize);
        }
        return layout;
    }

    ,trees:{}
    ,tree: function(el, opt) {
        var expanded = false, el = $(el);

        if (!opt) return Admin.load('trees', el);

        function checkLock(e) {
            if (opt.lock_flag && $(opt.lock_flag).get(0).checked) {
                alert('Locked');
                return false;
            }
            return true;
        }

        var plugins = ["themes","json_data","ui","crrm","cookies","dnd","search","types","hotkeys","contextmenu"];
        if (opt.checkbox) {
            plugins.push("checkbox");
        }

        el.jstree({
            'core': {
                animation:0
            },
            "plugins" : plugins,
            "json_data" : {
                "ajax" : {"url" : opt.url, "data" : function (n) {
                    return {
                        "operation" : "get_children",
                        "id" : n.attr ? n.attr("id").replace("node_","") : 1,
                        'expanded': expanded ? 1 : '',
                        'refresh': n!=-1 ? 1 : ''
                    };
                }
            }},
            // Configuring the search plugin
            "search" : {
                "ajax" : {"url" : opt.url, "data" : function (str) {
                    return { "operation" : "search", "search_str" : str};
                }
            }},
            "contextmenu": {
                'items': {
                    'select': {label:'Select', separator_before:true, action: function(n) { $.publish('select.jstree', n); }},
                    'reorder': {label:'Reorder A-Z', separator_before:true, action: function(n) {
                        if (!checkLock()) return;
                        function reorder(recursive) {
                            $.post(opt.url, {
                                operation:'reorderAZ',
                                id:n.attr("id").replace("node_",""),
                                recursive:recursive?1:''
                            }, function(data) { el.jstree('refresh', n); });
                        }
                        $('<div title="Reorder A-Z"></div>').dialog({
                            resizable:false, height:140, modal:true, buttons: {
                                'Only immediate children': function() { reorder(false); $(this).dialog( "close" ); },
                                'All descendants': function() { reorder(true); $(this).dialog( "close" ); }
                            }
                        });
                    }},
                    'refresh': {label:'Refresh', separator_before:true, action: function(n) { el.jstree('refresh', n); }}
                }
            },
            'cookies': { auto_save: false,save_opened: false,save_selected: false},
            'checkbox': { real_checkboxes:true }
        })
        .bind("before.jstree", function (e, data) {
            if (data.func.match(/(create|remove|rename|move_node)/) && !checkLock()) {
                e.stopImmediatePropagation();
                return false;
            }
            if (data.func=='remove' && !confirm('Are you sure?')) {
                e.stopImmediatePropagation();
                return false;
            }
        })
        .bind("create.jstree", function (e, data) {
            $.post(opt.url, {
                    "operation" : "create_node",
                    "id" : data.rslt.parent.attr("id").replace("node_",""),
                    "position" : data.rslt.position,
                    "title" : data.rslt.name,
                    "type" : data.rslt.obj.attr("rel")
                }, function (r) {
                    if(r.status) {
                        $(data.rslt.obj).attr("id", "node_" + r.id);
                    }
                    else {
                        alert(r.message);
                        $.jstree.rollback(data.rlbk);
                    }
                }
            );
        })
        .bind("remove.jstree", function (e, data) {
            data.rslt.obj.each(function () {
                $.ajax({
                    async : false,
                    type: 'POST',
                    url: opt.url,
                    data : {
                        "operation" : "remove_node",
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
        .bind("rename.jstree", function (e, data) {
            $.post(opt.url, {
                    "operation" : "rename_node",
                    "id" : data.rslt.obj.attr("id").replace("node_",""),
                    "title" : data.rslt.new_name
                }, function (r) {
                    if(!r.status) {
                        alert(r.message);
                        $.jstree.rollback(data.rlbk);
                    }
                }
            );
        })
        .bind("move_node.jstree", function (e, data) {
            data.rslt.o.each(function (i) {
                $.ajax({async : false, type: 'POST', url: opt.url,
                    data : {
                        "operation" : "move_node",
                        "id" : $(this).attr("id").replace("node_",""),
                        "ref" : data.rslt.cr === -1 ? 1 : data.rslt.np.attr("id").replace("node_",""),
                        "position" : data.rslt.cp + i,
                        "title" : data.rslt.name,
                        "copy" : data.rslt.cy ? 1 : 0
                    },
                    success : function (r) {
                        if(!r.status) {
                            alert(r.message);
                            $.jstree.rollback(data.rlbk);
                        }
                        else {
                            $(data.rslt.oc).attr("id", "node_" + r.id);
                            if(data.rslt.cy && $(data.rslt.oc).children("UL").length) {
                                data.inst.refresh(data.inst._get_parent(data.rslt.oc));
                            }
                        }
                        $("#analyze").click();
                    }
                });
            });
        });

        $('a', el).live('drop', function(e,dd) {
            if (!$(e.target).parents('.jstree').length || e.alreadyProcessed) {
                return;
            }
            e.alreadyProcessed = true;

            if (dd.grid) {
                var copy = e.ctrlKey, rowIDs = [];
                if (!confirm('Are you sure you want to '+(copy?'copy':'move')+' '+dd.count+' row(s) to '+e.target.innerText)) {
                    return;
                }
console.log(dd, $(dd.drag).parents('.grid-container'));
                for (var i=0; i<dd.rows.length; i++) {
                    rowIDs.push(dd.grid.getDataItem(dd.rows[i]).id);
                }
                $.post(opt.url, {
                        "operation" : "associate."+$(dd.drag).parents('.grid-container').attr('rel'),
                        "id" : $(this).parent().attr('id').replace('node_',''),
                        'ref': rowIDs,
                        'copy' : copy
                    },
                    function(r) {
                        console.log(r);
                    }
                );

                dd.grid.invalidate();
                dd.grid.setSelectedRows([]);
            }
        });

        Admin.save('trees', el);

        function toggleExpand() {
            $('#1 li', el).each(function(idx, li) { console.log(idx, li); el.jstree('toggle_node', li); });
        }

        return {toggleExpand:toggleExpand};
    }

    ,grids:{}
    ,slick: function(el, options) {
        var el = $(el), i, j;

        if (!options) return Admin.load('grids', el);

        var def = {
            grid: {editable:true, enableAddRow:true, enableCellNavigation:true}
        };

        var opt = $.extend(true, {}, def, options);

        opt.selectionModel = opt.selectionModel || {};
        for (i=0; i<opt.columns.length; i++) {
            for (j in {'editor':1, 'formatter':1, 'validator':1}) {
                if (typeof opt.columns[i][j]=='string') {
                    opt.columns[i][j] = eval(opt.columns[i][j]);
                }
            }
        }

        if (opt.checkboxSelector) {
            var checkboxSelector = new Slick.CheckboxSelectColumn({
                cssClass: 'slick-cell-checkboxsel'
            });
            opt.columns.unshift(checkboxSelector.getColumnDefinition());
        }

        if (opt.reorder) {
            opt.grid.enableRowReordering = true;
            opt.columns.unshift({id:'#', name:'', width:40, behavior:'selectAndMove', selectable:false, resizable:false, cssClass:'cell-reorder dnd'});
        }

        if (opt.undo) {
            var commandQueue = [];
            function queueAndExecuteCommand(item,column,editCommand) {
                commandQueue.push(editCommand);
                editCommand.execute();
            }
            function undo() {
                var command = commandQueue.pop();
                if (command && Slick.GlobalEditorLock.cancelCurrentEdit()) {
                    command.undo();
                    grid.gotoCell(command.row,command.cell,false);
                }
            }
            opt.grid.editCommandHandler = queueAndExecuteCommand;
        }

        //var dataView = new Admin.RemoteModel(opt.remote);
        var dataView = new Admin.RemoteDataView(opt);

        var grid = new Slick.Grid(el, dataView, opt.columns, opt.grid);

        if (opt.checkboxSelector) {
            //opt.selectionModel.selectActiveRow = false;
            grid.registerPlugin(checkboxSelector);
        }

        grid.setSelectionModel(new Slick.RowSelectionModel(opt.selectionModel));

        if (opt.reorder) {
            var moveRowsPlugin = new Slick.RowMoveManager();
            moveRowsPlugin.onBeforeMoveRows.subscribe(function(e,data) {
                for (var i = 0; i < data.rows.length; i++) {
                    // no point in moving before or after itself
                    if (data.rows[i] == data.insertBefore || data.rows[i] == data.insertBefore - 1) {
                        e.stopPropagation();
                        return false;
                    }
                }
                return true;
            });
            moveRowsPlugin.onMoveRows.subscribe(function(e,args) {
console.log(dataView);
                var extractedRows = [], left, right;
                var data = dataView.rows;
                var rows = args.rows;
                var insertBefore = args.insertBefore;
                left = data.slice(0,insertBefore);
                right = data.slice(insertBefore,data.length);

                for (var i=0; i<rows.length; i++) {
                    extractedRows.push(data[rows[i]]);
                }

                rows.sort().reverse();

                for (var i=0; i<rows.length; i++) {
                    var row = rows[i];
                    if (row < insertBefore)
                        left.splice(row,1);
                    else
                        right.splice(row-insertBefore,1);
                }

                data = left.concat(extractedRows.concat(right));

                var selectedRows = [];
                for (var i=0; i<rows.length; i++)
                    selectedRows.push(left.length+i);

                grid.resetActiveCell();
                grid.setData(data);
                grid.setSelectedRows(selectedRows);
                grid.render();
            });

            grid.registerPlugin(moveRowsPlugin);
        }

        if (opt.dnd) {
            grid.onDragInit.subscribe(function(e,dd) {
                // prevent the grid from cancelling drag'n'drop by default
                e.stopImmediatePropagation();
            });

            grid.onDragStart.subscribe(function(e,dd) {
                var data = dataView.rows;
                var cell = grid.getCellFromEvent(e);
                if (!cell)
                    return;

                dd.row = cell.row;
                if (!data[dd.row])
                    return;

                if (Slick.GlobalEditorLock.isActive())
                    return;

                e.stopImmediatePropagation();
                dd.mode = "recycle";

                var selectedRows = grid.getSelectedRows();

                if (!selectedRows.length || $.inArray(dd.row,selectedRows) == -1) {
                    selectedRows = [dd.row];
                    grid.setSelectedRows(selectedRows);
                }

                dd.rows = selectedRows;
                dd.count = selectedRows.length;

                var proxyText = opt.dnd.proxyTextCallback ? opt.dnd.proxyTextCallback(dd.count)
                    : dd.count+' row'+(dd.count>1?'s':'')+' selected';
                var proxy = $("<span></span>")
                    .css({
                        position: "absolute",
                        display: "inline-block",
                        padding: "4px 10px",
                        background: "#e0e0e0",
                        border: "1px solid gray",
                        "z-index": 99999,
                        "-moz-border-radius": "8px",
                        "-moz-box-shadow": "2px 2px 6px silver"
                        })
                    .text(proxyText)
                    .appendTo("body");

                opt.dnd.proxy = proxy;

                dd.helper = proxy;

                //$(dd.available).css("background","pink");

                return proxy;
            });

            grid.onDrag.subscribe(function(e,dd) {
                if (dd.mode != "recycle") {
                    return;
                }
                e.stopImmediatePropagation();
                dd.helper.css({top: e.pageY + 5, left: e.pageX + 5});
            });

            grid.onDragEnd.subscribe(function(e,dd) {
                if (dd.mode != "recycle") {
                    return;
                }
                e.stopImmediatePropagation();
                dd.helper.remove();
            });
        }
        if (opt.pager) {
            var pager = new Slick.Controls.Pager(dataView, grid, $(opt.pager.id));
        }
        if (opt.columnpicker) {
            var columnpicker = new Slick.Controls.ColumnPicker(opt.columns, grid, opt.grid);
        }
        var loadingIndicator = null;

        grid.onViewportChanged.subscribe(function(e,args) {
            var vp = grid.getViewport();
            dataView.ensureData(vp.top, vp.bottom);
        });

        grid.onSort.subscribe(function(e,args) {
            dataView.setSort(args.sortCol.field, args.sortAsc ? 1 : -1);
            var vp = grid.getViewport();
            dataView.ensureData(vp.top, vp.bottom);
        });

        grid.onAddNewRow.subscribe(function(e, args) {
            var item = {name:"New task", complete: false};
            $.extend(item, args.item);
            data.push(item);
            grid.invalidateRows([data.length - 1]);
            grid.updateRowCount();
            grid.render();
        });

        dataView.onDataLoading.subscribe(function() {
            el.css({opacity:.5});
        });

        dataView.onDataLoaded.subscribe(function(e,args) {
            for (var i = args.from; i <= args.to; i++) {
                grid.invalidateRow(i);
            }
            grid.updateRowCount();
            grid.render();

            el.css({opacity:1});
        });

/*
        $("#txtSearch").keyup(function(e) {
            if (e.which == 13) {
                loader.setSearch($(this).val());
                var vp = grid.getViewport();
                loader.ensureData(vp.top, vp.bottom);
            }
        });
*/
        // load the first page
        grid.onViewportChanged.notify();

        if (opt.sub && opt.sub.resize) {
            $.subscribe('layouts/'+opt.sub.resize+'/resize', function (w, h) {
console.log(w, h);
                el.css({width:w-20, height:h-20});
                grid.resizeCanvas();
            });
        }

        if (opt.after) opt.after();

        Admin.save('grids', el, grid);
        return grid;
    }

    ,RemoteModel: function(opt) {
        // private
        var PAGESIZE = 50;
        var data = {length:0};
        var searchstr = "";
        var sortcol = null;
        var sortdir = 1;
        var h_request = null;
        var req = null; // ajax request

        // events
        var onDataLoading = new Slick.Event();
        var onDataLoaded = new Slick.Event();

        var onRowCountChanged = new Slick.Event();
        var onRowsChanged = new Slick.Event();
        var onPagingInfoChanged = new Slick.Event();

        function init() {
        }

        function isDataLoaded(from,to) {
            for (var i=from; i<=to; i++) if (data[i] == undefined || data[i] == null) return false;
            return true;
        }

        function clear() {
            for (var key in data) delete data[key];
            data.length = 0;
        }

        function ensureData(from,to) {
            if (req) {
                req.abort();
                for (var i=req.from; i<=req.to; i++) data[i] = undefined;
            }
            if (from < 0) from = 0;
            while (data[from] !== undefined && from < to) from++;
            while (data[to] !== undefined && from < to) to--;

            if (from >= to) {
                // TODO:  look-ahead
                return;
            }

            var url = opt.url+'?q='+searchstr+"&rs="+from+"&rc="+(to-from+1);
            if (sortcol) url += '&s='+sortcol+'&sd='+(sortdir>0?'asc':'desc');
            if (h_request != null) clearTimeout(h_request);

            h_request = setTimeout(function() {
                for (var i=from; i<=to; i++) data[i] = null;
                onDataLoading.notify({from:from, to:to});
                req = $.get(url, onSuccess);
                req.from = from;
                req.to = to;
            }, 50);
        }

        function onSuccess(resp) {
            var from = parseInt(resp.state.rs), to = from + parseInt(resp.state.rc);
            data.length = parseInt(resp.state.c);
            for (var i = 0; i < resp.rows.length; i++) {
                data[from+i] = resp.rows[i];
                data[from+i].index = from+i;
            }
            req = null;
            onDataLoaded.notify({from:from, to:to});
        }

        function reloadData(from,to) {
            for (var i=from; i<=to; i++) delete data[i];
            ensureData(from,to);
        }

        function setPagingOptions(args) {
            if (args.pageSize != undefined)
                pagesize = args.pageSize;

            if (args.pageNum != undefined)
                pagenum = Math.min(args.pageNum, Math.ceil(totalRows / pagesize));

            onPagingInfoChanged.notify(getPagingInfo(), null, self);

            refresh();
        }

        function getPagingInfo() {
            return {pageSize:pagesize, pageNum:pagenum, totalRows:totalRows};
        }

        function setSort(column,dir) {
            sortcol = column;
            sortdir = dir;
            clear();
        }

        function setSearch(str) {
            searchstr = str;
            clear();
        }

        init();

        return {
            // properties
            "data": data

            // methods
            ,"clear": clear
            ,"isDataLoaded": isDataLoaded
            ,"ensureData": ensureData
            ,"reloadData": reloadData
            ,"setSort": setSort
            ,"setSearch": setSearch

            ,"setPagingOptions": setPagingOptions
            ,"getPagingInfo": getPagingInfo

            // events
            ,"onDataLoading": onDataLoading
            ,"onDataLoaded": onDataLoaded
            ,"onRowCountChanged": onRowCountChanged
            ,"onRowsChanged": onRowsChanged
            ,"onPagingInfoChanged": onPagingInfoChanged
        };
    },

    RemoteDataView: function(options) {
        var self = this;

        var defaults = {
            groupItemMetadataProvider: null
        };


        // private
        var idProperty = "id";  // property holding a unique row id
        var items = [];            // data by index
        var rows = {length:0};            // data by row
        var idxById = {};        // indexes by id
        var rowsById = null;    // rows by id; lazy-calculated
        var filter = null;        // filter function
        var updated = null;     // updated item ids
        var suspend = false;    // suspends the recalculation
        var sortField;
        var sortAsc = true;

        // grouping
        var groupingGetter;
        var groupingGetterIsAFn;
        var groupingFormatter;
        var groupingComparer;
        var groups = [];
        var collapsedGroups = {};
        var aggregators;
        var aggregateCollapsed = false;

        var pagesize = 0;
        var pagenum = 0;
        var totalRows = 0;

        var h_request = null;
        var req = null; // ajax request

        // events
        var onDataLoading = new Slick.Event();
        var onDataLoaded = new Slick.Event();
        var onRowCountChanged = new Slick.Event();
        var onRowsChanged = new Slick.Event();
        var onPagingInfoChanged = new Slick.Event();

        options = $.extend(true, {}, defaults, options);


        function beginUpdate() {
            suspend = true;
        }

        function endUpdate(hints) {
            suspend = false;
            refresh(hints);
        }

        function updateIdxById(startingIndex) {
            startingIndex = startingIndex || 0;
            var id;
            for (var i = startingIndex, l = items.length; i < l; i++) {
                id = items[i][idProperty];
                if (id === undefined) {
                    throw "Each data element must implement a unique 'id' property";
                }
                idxById[id] = i;
            }
        }

        function ensureIdUniqueness() {
            var id;
            for (var i = 0, l = items.length; i < l; i++) {
                id = items[i][idProperty];
                if (id === undefined || idxById[id] !== i) {
                    throw "Each data element must implement a unique 'id' property";
                }
            }
        }

        function getItems() {
            return items;
        }

        function setItems(data, objectIdProperty) {
            if (objectIdProperty !== undefined) idProperty = objectIdProperty;
            items = data;
            idxById = {};
            updateIdxById();
            ensureIdUniqueness();
            refresh();
        }

///////////////////////////// REMOTEMODEL:START
        function isDataLoaded(from,to) {
            for (var i=from; i<=to; i++) if (rows[i] == undefined || rows[i] == null) return false;
            return true;
        }

        function clear() {
            for (var key in rows) delete rows[key];
            rows.length = 0;
        }

        function ensureData(from,to) {
            if (req) {
                req.abort();
                for (var i=req.from; i<=req.to; i++) rows[i] = undefined;
            }
            if (from < 0) from = 0;
            while (rows[from] !== undefined && from < to) from++;
            while (rows[to] !== undefined && from < to) to--;

            if (from >= to) {
                // TODO:  look-ahead
                return;
            }

            var url = options.url+"?";
            if (pagesize) {
                url += "&ps="+pagesize+"&p="+pagenum;
            } else {
                url += "&rs="+from+"&rc="+(to-from+1);
            }
            if (sortField) url += '&s='+sortField+'&sd='+sortAsc;
            if (h_request != null) clearTimeout(h_request);

            h_request = setTimeout(function() {
                for (var i=from; i<=to; i++) rows[i] = null;
                onDataLoading.notify({from:from, to:to});
                req = $.get(url, onSuccess);
                req.from = from;
                req.to = to;
            }, 50);
        }

        function onSuccess(resp) {
            var from = 1*resp.state.rs, to = from + 1*resp.state.rc;
            rows.length = parseInt(resp.state.c);
            totalRows = resp.state.c;
            for (var i = 0; i < resp.rows.length; i++) {
                rows[from+i] = resp.rows[i];
                rows[from+i].index = from+i;
            }
            req = null;
            onDataLoaded.notify({from:from, to:to});
        }

        function reloadData(from,to) {
            for (var i=from; i<=to; i++) delete rows[i];
            ensureData(from,to);
        }
///////////////////////////// REMOTEMODEL:END

        function setPagingOptions(args) {
            if (args.pageSize != undefined)
                pagesize = args.pageSize;

            if (args.pageNum != undefined)
                pagenum = Math.min(args.pageNum, Math.ceil(totalRows / pagesize));

            onPagingInfoChanged.notify(getPagingInfo(), null, self);

            refresh();
        }

        function getPagingInfo() {
            return {pageSize:pagesize, pageNum:pagenum, totalRows:totalRows};
        }

        function setSort(s, a) {
console.log(a);
            sortField = s;
            sortAsc = a>0 ? 'asc' : 'desc';
            refresh();
        }

        /***
        * Provides a workaround for the extremely slow sorting in IE.
        * Does a [lexicographic] sort on a give column by temporarily overriding Object.prototype.toString
        * to return the value of that field and then doing a native Array.sort().
        */
/*
        function fastSort(field, ascending) {
            sortAsc = ascending;
            fastSortField = field;
            sortComparer = null;
            var oldToString = Object.prototype.toString;
            Object.prototype.toString = (typeof field == "function")?field:function() { return this[field] };
            // an extra reversal for descending sort keeps the sort stable
            // (assuming a stable native sort implementation, which isn't true in some cases)
            if (ascending === false) items.reverse();
            items.sort();
            Object.prototype.toString = oldToString;
            if (ascending === false) items.reverse();
            idxById = {};
            updateIdxById();
            refresh();
        }

        function reSort() {
            if (sortComparer) {
               sort(sortComparer, sortAsc);
            }
            else if (fastSortField) {
               fastSort(fastSortField, sortAsc);
            }
        }
*/
        function setFilter(filterFn) {
            filter = filterFn;
            refresh();
        }

        function groupBy(valueGetter, valueFormatter, sortComparer) {
            if (!options.groupItemMetadataProvider) {
                options.groupItemMetadataProvider = new Slick.Data.GroupItemMetadataProvider();
            }

            groupingGetter = valueGetter;
            groupingGetterIsAFn = typeof groupingGetter === "function";
            groupingFormatter = valueFormatter;
            groupingComparer = sortComparer;
            collapsedGroups = {};
            groups = [];
            refresh();
        }

        function setAggregators(groupAggregators, includeCollapsed) {
            aggregators = groupAggregators;
            aggregateCollapsed = includeCollapsed !== undefined ? includeCollapsed : aggregateCollapsed;
            refresh();
        }

        function getItemByIdx(i) {
            return items[i];
        }

        function getIdxById(id) {
            return idxById[id];
        }

        // calculate the lookup table on first call
        function getRowById(id) {
            if (!rowsById) {
                rowsById = {};
                for (var i = 0, l = rows.length; i < l; ++i) {
                    rowsById[rows[i][idProperty]] = i;
                }
            }

            return rowsById[id];
        }

        function getItemById(id) {
            return items[idxById[id]];
        }

        function updateItem(id, item) {
            if (idxById[id] === undefined || id !== item[idProperty])
                throw "Invalid or non-matching id";
            items[idxById[id]] = item;
            if (!updated) updated = {};
            updated[id] = true;
            refresh();
        }

        function insertItem(insertBefore, item) {
            items.splice(insertBefore, 0, item);
            updateIdxById(insertBefore);
            refresh();
        }

        function addItem(item) {
            items.push(item);
            updateIdxById(items.length - 1);
            refresh();
        }

        function deleteItem(id) {
            var idx = idxById[id];
            if (idx === undefined) {
                throw "Invalid id";
            }
            delete idxById[id];
            items.splice(idx, 1);
            updateIdxById(idx);
            refresh();
        }

        function getLength() {
            return rows.length;
        }

        function getItem(i) {
            return rows[i];
        }

        function getItemMetadata(i) {
            var item = rows[i];
            if (item === undefined || item === null) {
                return null;
            }

            // overrides for group rows
            if (item.__group) {
                return options.groupItemMetadataProvider.getGroupRowMetadata(item);
            }

            // overrides for totals rows
            if (item.__groupTotals) {
                return options.groupItemMetadataProvider.getTotalsRowMetadata(item);
            }

            return null;
        }

        function collapseGroup(groupingValue) {
            collapsedGroups[groupingValue] = true;
            refresh();
        }

        function expandGroup(groupingValue) {
            delete collapsedGroups[groupingValue];
            refresh();
        }

        function getGroups() {
            return groups;
        }

        function extractGroups(rows) {
            var group;
            var val;
            var groups = [];
            var groupsByVal = {};
            var r;

            for (var i = 0, l = rows.length; i < l; i++) {
                r = rows[i];
                val = (groupingGetterIsAFn) ? groupingGetter(r) : r[groupingGetter];
                group = groupsByVal[val];
                if (!group) {
                    group = new Slick.Group();
                    group.count = 0;
                    group.value = val;
                    group.rows = [];
                    groups[groups.length] = group;
                    groupsByVal[val] = group;
                }

                group.rows[group.count++] = r;
            }

            return groups;
        }

        // TODO:  lazy totals calculation
        function calculateGroupTotals(group) {
            var r, idx;

            if (group.collapsed && !aggregateCollapsed) {
                return;
            }

            idx = aggregators.length;
            while (idx--) {
                aggregators[idx].init();
            }

            for (var j = 0, jj = group.rows.length; j < jj; j++) {
                r = group.rows[j];
                idx = aggregators.length;
                while (idx--) {
                    aggregators[idx].accumulate(r);
                }
            }

            var t = new Slick.GroupTotals();
            idx = aggregators.length;
            while (idx--) {
                aggregators[idx].storeResult(t);
            }
            t.group = group;
            group.totals = t;
        }

        function calculateTotals(groups) {
            var idx = groups.length;
            while (idx--) {
                calculateGroupTotals(groups[idx]);
            }
        }

        function finalizeGroups(groups) {
            var idx = groups.length, g;
            while (idx--) {
                g = groups[idx];
                g.collapsed = (g.value in collapsedGroups);
                g.title = groupingFormatter ? groupingFormatter(g) : g.value;
            }
        }

        function flattenGroupedRows(groups) {
            var groupedRows = [], gl = 0, idx, t, g, r;
            for (var i = 0, l = groups.length; i < l; i++) {
                g = groups[i];
                groupedRows[gl++] = g;

                if (!g.collapsed) {
                    for (var j = 0, jj = g.rows.length; j < jj; j++) {
                        groupedRows[gl++] = g.rows[j];
                    }
                }

                if (g.totals && (!g.collapsed || aggregateCollapsed)) {
                    groupedRows[gl++] = g.totals;
                }
            }
            return groupedRows;
        }

        function getFilteredAndPagedItems(items, filter) {
            var pageStartRow = pagesize * pagenum;
            var pageEndRow = pageStartRow + pagesize;
            var itemIdx = 0, rowIdx = 0, item;
            var newRows = [];

            // filter the data and get the current page if paging
            if (filter) {
                for (var i = 0, il = items.length; i < il; ++i) {
                    item = items[i];

                    if (!filter || filter(item)) {
                        if (!pagesize || (itemIdx >= pageStartRow && itemIdx < pageEndRow)) {
                            newRows[rowIdx] = item;
                            rowIdx++;
                        }
                        itemIdx++;
                    }
                }
            }
            else {
                newRows = pagesize ? items.slice(pageStartRow, pageEndRow) : items.concat();
                itemIdx = items.length;
            }

            return {totalRows:itemIdx, rows:newRows};
        }

        function getRowDiffs(rows, newRows) {
            var item, r, eitherIsNonData, diff = [];
            for (var i = 0, rl = rows.length, nrl = newRows.length; i < nrl; i++) {
                if (i >= rl) {
                    diff[diff.length] = i;
                }
                else {
                    item = newRows[i];
                    r = rows[i];

                    if ((groupingGetter && (eitherIsNonData = (item.__nonDataRow) || (r.__nonDataRow)) &&
                            item.__group !== r.__group ||
                            item.__updated ||
                            item.__group && !item.equals(r))
                        || (aggregators && eitherIsNonData &&
                            // no good way to compare totals since they are arbitrary DTOs
                            // deep object comparison is pretty expensive
                            // always considering them 'dirty' seems easier for the time being
                            (item.__groupTotals || r.__groupTotals))
                        || item[idProperty] != r[idProperty]
                        || (updated && updated[item[idProperty]])
                        ) {
                        diff[diff.length] = i;
                    }
                }
            }
            return diff;
        }

        function recalc(_items, _rows, _filter) {
            rowsById = null;

            var newRows = [];

            var filteredItems = getFilteredAndPagedItems(_items, _filter);
            totalRows = filteredItems.totalRows;
            newRows = filteredItems.rows;

            groups = [];
            if (groupingGetter != null) {
                groups = extractGroups(newRows);
                if (groups.length) {
                    finalizeGroups(groups);
                    if (aggregators) {
                        calculateTotals(groups);
                    }
                    groups.sort(groupingComparer);
                    newRows = flattenGroupedRows(groups);
                }
            }

            var diff = getRowDiffs(_rows, newRows);

            rows = newRows;

            return diff;
        }

        function refresh() {
            if (suspend) return;

            var countBefore = rows.length;
            var totalRowsBefore = totalRows;

            var diff = recalc(items, rows, filter); // pass as direct refs to avoid closure perf hit

            // if the current page is no longer valid, go to last page and recalc
            // we suffer a performance penalty here, but the main loop (recalc) remains highly optimized
            if (pagesize && totalRows < pagenum * pagesize) {
                pagenum = Math.floor(totalRows / pagesize);
                diff = recalc(items, rows, filter);
            }

            updated = null;

            if (totalRowsBefore != totalRows) onPagingInfoChanged.notify(getPagingInfo(), null, self);
            if (countBefore != rows.length) onRowCountChanged.notify({previous:countBefore, current:rows.length}, null, self);
            if (diff.length > 0) onRowsChanged.notify({rows:diff}, null, self);
        }

        onPagingInfoChanged.subscribe(function(e,pagingInfo) {
            ensureData(pagingInfo.pageSize*pagingInfo.pageNum, pagingInfo.pageSize*(pagingInfo.pageNum+1));
            refresh();
        });

        return {
            rows: rows,
            items: items,

            // methods
            "beginUpdate":      beginUpdate,
            "endUpdate":        endUpdate,
            "setPagingOptions": setPagingOptions,
            "getPagingInfo":    getPagingInfo,
            "getItems":         getItems,
            "setItems":         setItems,
            "setFilter":        setFilter,
            "setSort":          setSort,
            //"fastSort":         fastSort,
            //"reSort":           reSort,
            "groupBy":          groupBy,
            "setAggregators":   setAggregators,
            "collapseGroup":    collapseGroup,
            "expandGroup":      expandGroup,
            "getGroups":        getGroups,
            "getIdxById":       getIdxById,
            "getRowById":       getRowById,
            "getItemById":      getItemById,
            "getItemByIdx":     getItemByIdx,
            "refresh":          refresh,
            "updateItem":       updateItem,
            "insertItem":       insertItem,
            "addItem":          addItem,
            "deleteItem":       deleteItem,

            "clear":            clear,
            "isDataLoaded":     isDataLoaded,
            "ensureData":       ensureData,
            "reloadData":       reloadData,

            // data provider methods
            "getLength":        getLength,
            "getItem":          getItem,
            "getItemMetadata":  getItemMetadata,


            // events
            "onDataLoading":    onDataLoading,
            "onDataLoaded":     onDataLoaded,
            "onRowCountChanged":    onRowCountChanged,
            "onRowsChanged":        onRowsChanged,
            "onPagingInfoChanged":  onPagingInfoChanged
        };
    },

    forms: {},
    form: function(options) {
        /* options = {
            tabs:'.adm-tabs-left li',
            panes:'.adm-tabs-content',
            url_get: '.../form_tab/:id',
            url_post: '.../edit/:id'
        } */
        var tabs, panes, curLi, curPane, editors = {};

        function loadTabs(data) {
            for (var i in data.tabs) {
                $('#tab-'+i).html(data.tabs[i]).data('loaded', true);
            }
        }

        function wysiwygCreate(id) {
            if (!editors[id]) {
                editors[id] = true; // prevent double loading
                $('#'+id).ckeditor(function() {
                    this.dataProcessor.writer.indentationChars = '  ';
                    editors[id] = this;
                });
            }
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


        function tabClass(id, cls) {
            var tab = $('a[href=#tab-'+id+']', tabs).parent('li');
            tab.removeClass('dirty error');
            if (cls) tab.addClass(cls);
        }

        function tabAction(action, el) {
            var pane = $(el).parents(options.panes);
            var tabId = pane.attr('id').replace(/^tab-/,'');
            switch (action) {
            case 'edit':
                $.get(options.url_get+'?tabs='+tabId+'&mode=edit', function(data, status, req) {
                    loadTabs(data);
                    tabClass(tabId, 'dirty');
                });
                break;

            case 'cancel':
                $.get(options.url_get+'?tabs='+tabId+'&mode=view', function(data, status, req) {
                    loadTabs(data);
                    tabClass(tabId);
                });
                break;

            case 'save':
                for (var i in editors) {
                    editors[i].updateElement();
                }
                var postData = $(el).parents('fieldset').find('input,select,textarea').serializeArray();
                $.post(options.url_post+'?tabs='+tabId+'&mode=view', postData, function(data, status, req) {
                    loadTabs(data);
                    tabClass(tabId);
                });
                break;

            case 'dirty':
                $('a[href=#'+tabId+']', tabs).addClass('changed');
                break;

            case 'clean':
                $('a[href=#'+tabId+']', tabs).removelass('changed');
                break;
            }
            return false;
        }

        function saveAll(el) {
            return true;
            //TODO
            var form = $(el).parents('form');
            var postData = form.serializeArray();
            $.post(options.url_post+'?tabs=ALL&mode=view', postData, function(data, status, req) {
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

        $(function() {
            var tabs = $(options.tabs);
            var panes = $(options.panes);
            var curLi = $(options.tabs+'[class=active]');
            var curPane = $(options.panes+':not([hidden])');

            $('a', tabs).click(function(ev) {
                curLi.removeClass('active');
                curPane.attr('hidden', 'hidden');
                ev.stopPropagation();

                var a = $(ev.currentTarget), li = a.parent('li');
                if (curLi===li) {
                    return false;
                }
                var pane = $(a.attr('href'));
                li.addClass('active');
                pane.removeAttr('hidden');
                curLi = li;
                curPane = pane;
                var tabId = a.attr('href').replace(/^#tab-/,'');
                pane.parents('form').find('#tab').val(tabId);
                if (!pane.data('loaded')) {
                    $.getJSON(options.url_get+'?tabs='+tabId, function(data, status, req) {
                        loadTabs(data);
                    });
                }
                return false;
            });
        });

        return {
            loadTabs:loadTabs,
            wysiwygCreate:wysiwygCreate,
            wysiwygDestroy:wysiwygDestroy,
            tabClass:tabClass,
            tabAction:tabAction,
            saveAll:saveAll,
            deleteForm:deleteForm
        };
    }
}
/*
$.extend($.jgrid.defaults, {
});
*/

function jqgrid(id, options) {
    var i, grid = $("#"+id), opt = {
        grid: {
            mtype:'POST'
            ,datatype: "json"
            ,jsonReader: {root:'rows', page:'p', total:'mp', records:'c', repeatitems:false, id:'id'}
            ,rowList : [20,30,50]
            //,scroll:1
            //,loadonce:true
            ,gridview: true
            ,viewrecords: true
            ,pager: '#'+id+'-pager'
            ,shrinkToFit:true
            ,autowidth:true
            //,altRows:true
            ,height:'100%'
        },
        nav: {
            params: { add:true, del:true, edit:true }
        }
    };
    $.extend(true, opt, options);
    if (!grid.length) {
        $(opt.parent).append('<table id="'+id+'"></table><div id="'+id+'-pager"></div>');
        grid = $("#"+id);
    }
    if (opt.tableDnD) {
        grid.tableDnD(opt.tableDnD);
        $.extend(true, opt.grid, {
            gridComplete: function() {
                $("#_empty",grid).addClass("nodrag nodrop");
                grid.tableDnDUpdate();
            }
        });
    }
    if (opt.tree) {
        $.extend(true, opt.grid, {
            treeGrid: true
            ,treeGridModel: 'adjacency'
            ,treeReader: {
                level_field:'level'
                ,parent_id_field:'parent_id'
                ,leaf_field:'is_leaf'
                ,expanded_field:'is_expanded'
            }
            ,gridView:false
            ,ExpandColumn:'node_name'
        }, opt.tree);
    }
    grid.jqGrid(opt.grid);

    function autoresize() {
        var p = $(opt.parent) || grid.parents('.ui-layout-pane');
        grid.jqGrid('setGridWidth', p.width()-20).jqGrid('setGridHeight', p.height()-80);
    }

    if (opt.nav) {
        grid.jqGrid('navGrid','#'+id+'-pager', opt.nav.params||{}, opt.nav.edit||{},
            opt.nav.add||{}, opt.nav.del||{}, opt.nav.search||{}, opt.nav.view||{});
    }
    if (opt.plugins) {
        for (i in opt.plugins) grid.jqGrid(i, opt.plugins[i]);
    }

    return {autoresize:autoresize};
}
jqgrid.fmtHiddenInput = function (cellvalue, options, rowObject) {
    console.log(cellvalue,options,rowObject);
   // do something here
   return cellvalue ? cellvalue : '';
}

$.widget('ui.fcom_autocomplete', {
    _create: function() {
        var self = this, input = this.element, field = $(this.options.field), value = field.val();
        var cache = {}, lastXhr;
        var options = $.extend({
            minLength:0,
            source: function(request, response) {
                var term = request.term;
                if (term in cache) {
                    response(cache[term]);
                    return;
                }
                var url = self.options.url, query = $(self.options.filter).serialize();
                if (query) {
                    url += (url.match(/\?/) ? '&' : '?') + query;
                }
                lastXhr = $.getJSON(url, request, function(data, status, xhr) {
                    cache[term] = data;
                    if (xhr === lastXhr) {
                        response(data);
                    }
                });
            },
            select: function( event, ui ) {
                field.val(ui.item.id);
                self.options.select && self.options.select(event, ui);
            },
            change: function( event, ui ) {
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
        input.autocomplete(options).focus(function(ev) { if (!$(this).val()) { $(this).autocomplete('search', ''); } });
    },

    destroy: function() {
        this.input.remove();
        this.button.remove();
        this.element.show();
        $.Widget.prototype.destroy.call( this );
    }
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
        req.push(encodeURIComponent(i)+'='+encodeURIComponent(params[i]));
    }
    el.css({opacity:.5});
    el.load(options.src+(options.src&&options.src.match(/\?/)?'&':'?')+req.join('&'), function(data) {
        $('.scrollable', el).scrollTop(scroll);
        el.css({opacity:1});
        if (typeof options.complete!=='undefined') options.complete();
    });
}

function partialParent(el, params) {
    partial($(el).closest('.include'), params);
}

function jqgridFmtNewWindow(val,opt,obj) {
    return "<a href='javascript:window.open(\""+val+"\", \"vendor_website_url\", \"width=800,height=600\")'>"+val+"</a>";
}

$(function(){
    $.jgrid.formatter.date.newformat = 'm/d/Y';
    $.jgrid.edit.width = 500;

    if (typeof CKEDITOR !== 'undefined') {
        CKEDITOR.config.autoUpdateElement = true;
        CKEDITOR.config.toolbarStartupExpanded = false;
        CKEDITOR.config.startupMode = 'source';
    }
    //$('.datepicker').datepicker();
    $(document).bind('ajaxSuccess', function(event, request, settings) {
        if (settings.dataType=='json' && (data = $.parseJSON(request.responseText))) {
            if (data.error=='login') {
                location.href = window.appConfig.baseHref;
            }
        }
    });
    $('.nav-group header').click(function(ev) {
        $(ev.currentTarget).parent('li').find('ul').animate({
            opacity:'toggle',
            height:'toggle'
        }, 100);
    });
})
