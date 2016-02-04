var fcomAdminDeps = ["jquery", 'bootstrap-ladda', "jquery-ui", "bootstrap", "fcom.core", 'ckeditor', 'jquery.bootstrap-growl', 'switch', 'jquery.pnotify', 'bootstrap-ladda-spin'];
if (require.specified('ckeditor')) {
    fcomAdminDeps.push('ckeditor');
}

/**
 * global object contains all modules, libraries, functions
 * @name FCom
 * @type {Object}
 * @property base_href
 */

/**
 * object contains all features for admin base
 * @name FCom#Admin
 * @type {Object}
 * @property {String} base_url
 * @property {String} code_mirror_base_url
 * @property {String} upload_href - url for upload
 * @property {String} personalize_href - url to store personalize info
 * @property {String} current_mode current application mode
 */

define(fcomAdminDeps, function ($, Ladda) {
    /*
     var myApp = angular.module("fcomApp", [], function($interpolateProvider) {
     $interpolateProvider.startSymbol("<%");
     $interpolateProvider.endSymbol("%>");
     });
     */

    /**
     * log value to console in mode DEBUG
     * @param {string} text
     */
    FCom.Admin.log = function (text) {
        if (FCom.Admin.current_mode == 'DEBUG') {
            console.log(text);
        }
    };

    /**
     * @param {String} containerId
     * @param {Object} options
     */
    FCom.Admin.Accordion = function (containerId, options) {
        var $container = $('#' + containerId);
        $container.find('.accordion-body').each(function (i, bodyEl) {
            var $bodyEl = $(bodyEl), $headingEl = $bodyEl.siblings('.panel-heading').find('a');
            $bodyEl.addClass('collapse');
            $headingEl.attr('data-parent', '#' + containerId);
            if (!$bodyEl.attr('id')) {
                $bodyEl.attr('id', containerId + '-group' + i);
                $headingEl.attr('href', '#' + containerId + '-group' + i);
            }
        });
    };

    /**
     * @callback tab_load_callback - call back function when tab load
     * @param {Number} i
     * @param {String} tabHtml
     */
    /**
     * @param {String} containerSel container element
     * @param {Object} options
     * @param {String} options.cur_tab current tab id
     * @param {String} options.url_get url to get content for tab
     * @param {tab_load_callback} options.tab_load_callback
     */
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
    };

    /**
     * media library
     * @param options
     * @returns {{setOptions: setOptions, getSelectedRows: getSelectedRows}}
     * @constructor
     * todo: consider use this??????????
     */
    FCom.Admin.MediaLibrary = function (options) {
        var grid = $(options.grid || '#media-library'), container = grid.parents('.ui-jqgrid').parent();
        var baseUrl = options.url + '/download?folder=' + encodeURIComponent(options.folder) + '&file=';

        /**
         * set options
         * @param opt
         */
        function setOptions(opt) {
            for (i in opt) {
                options[i] = opt[i];
            }
        }

        /**
         *
         * @param ev
         * @returns {boolean}
         */
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

        /**
         *
         * @param ev
         * @returns {boolean}
         */
        function editAttachmentSave(ev) {
            var el = $(ev.target), tr = el.parents('tr'), rowid = tr.attr('id');
            ev.stopPropagation();
            grid.jqGrid('saveRow', rowid);
            editAttachmentRestore(tr);
            return false;
        }

        /**
         *
         * @param ev
         * @returns {boolean}
         */
        function editAttachmentCancel(ev) {
            var el = $(ev.target), tr = el.parents('tr'), rowid = tr.attr('id');
            ev.stopPropagation();
            grid.jqGrid('restoreRow', rowid);
            editAttachmentRestore(tr);
            return false;
        }

        /**
         *
         * @param tr
         */
        function editAttachmentRestore(tr) {
            $('.ui-icon-disk,.ui-icon-cancel', tr).hide('fast');
            $('.ui-icon-pencil', tr).show('fast');
        }

        /**
         *
         * @param ev
         * @param {Boolean} inline
         * @returns {boolean}
         */
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

        /**
         *
         * @returns {(Array|Object)}
         */
        function getSelectedRows() {
            if (grid.jqGrid('getGridParam', 'multiselect')) {
                return grid.jqGrid('getGridParam', 'selarrrow');
            } else {
                var sel = grid.jqGrid('getGridParam', 'selrow');
                return sel === null ? [] : [sel];
            }
        }

        /**
         *
         * @returns {boolean}
         */
        function deleteAttachments() {
            if (!confirm('Are you sure?')) {
                return false;
            }
            var sel = getSelectedRows(), i, postData = {'delete[]': []};
            if (!sel.length) {
                alert('Please select some attachments to delete.');
                return false;
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

        /**
         *
         * @param val
         * @param opt
         * @param obj
         * @returns {string|*}
         */
        function fmtActions(val, opt, obj) {
            var html = '';
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
            return downloadAttachment(ev, false);
        });
        $(grid).on('click', '.ui-icon-arrowreturnthick-1-e', function (ev) {
            return downloadAttachment(ev, true);
        });

        var colModel = grid[0].p.colModel;
        for (var i = 0; i < colModel.length; i++) {
            switch (colModel[i].name) {
                case 'file_size':
                    colModel[i].formatter = function (val, opt, obj) {
                        return Math.round(val / 1024) + 'k';
                    };
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
            FCom.Admin.log(this.files);
            var form = $(this).parents('form'), action = form.attr('action'), i, file;
            for (i = 0; i < this.files.length; i++) {
                file = this.files[i];
                FCom.Admin.log(file);
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
    };

    /**
     *
     * @param options
     * @returns {{}}
     * @constructor
     */
    FCom.Admin.TargetGrid = function (options) {
        var source = $(options.source), target = $(options.target);
        var id = options.id || target.attr('id');
        var addInput = $('<input type="hidden" name="grid[' + id + '][add]" value=""/>');
        var delInput = $('<input type="hidden" name="grid[' + id + '][del]" value=""/>');
        target.parents('.ui-jqgrid').append(addInput, delInput);

        /**
         * add rows
         */
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

        /**
         * remove rows
         */
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

        /**
         * update products
         * @param action
         * @param sel
         */
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
    };

    /**
     *
     * @param collection
     * @param el
     * @returns {*}
     */
    FCom.Admin.load = function (collection, el) {
        el = $(el);
        var uid = el.data('uid');
        return FCom.Admin[collection][uid];
    };

    /**
     *
     * @param collection
     * @param el
     * @param object
     */
    FCom.Admin.save = function (collection, el, object) {
        var uid = Math.random();
        el.data('uid', uid);
        FCom.Admin[collection][uid] = object || el;
    };

    /**
     *
     * @param id
     * @param opt
     * @returns {*|jQuery|HTMLElement}
     */
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
    };

    /**
     *
     * @param id
     * @param opt
     * @returns {*|jQuery|HTMLElement}
     */
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
    };

    /**
     * process add image button in form - get / remove image from image library
     * @param {Object} dataConfig
     * @param {String} dataConfig.config_id
     * @param {String} dataConfig.text_add_image
     * @param {String} dataConfig.text_change_image
     * @param {String} dataConfig.text_modal_change
     * @param {String} dataConfig.text_modal_add
     * @param {String} dataConfig.resize_url
     */
    FCom.Admin.buttonAddImage = function (dataConfig) {
        var $buttonAddImage = $('.btn_'+ dataConfig.config_id +'_add');
        var textBtnAddImage = '.' + dataConfig.config_id + '_btn_add_image';
        var textBtnRemoveImage = '.' + dataConfig.config_id + '_btn_remove_image';
        $('body').on('click', textBtnAddImage,function() {
            if (!$(this).hasClass('active')) {
                $(textBtnAddImage).removeClass('active');
                $(this).addClass('active');
            }
            dataConfig.grid.getGridView().clearSelectedRows();
            $buttonAddImage.addClass('disabled');
            if ($(this).hasClass('data-change')) {
                $buttonAddImage.html(dataConfig.text_modal_change);
            } else {
                $buttonAddImage.html(dataConfig.text_modal_add);
            }
            $('#'+ dataConfig.config_id +'_modal').modal();

        }).on('click', textBtnRemoveImage, function() {
            processImage(this, {
                text: dataConfig.text_add_image,
                display: 'none',
                image_tag: '',
                path: ''
            });
            $(this).parents('.form-group').find(textBtnAddImage).removeClass('data-change');
        });
        function processImage(el, data) {
            var parents = $(el).parents('.form-group');
            parents.find('.'+ dataConfig.config_id +'_btn_add_text').html(data.text);
            parents.find(textBtnRemoveImage).css('display', data.display);
            parents.find('.'+ dataConfig.config_id +'_current_image').html(data.image_tag);
            parents.find('.model_image_url').val(data.path);
        }

        $buttonAddImage.click(function() {
            var row = dataConfig.grid.getSelectedRows().at(0);
            var path = row.get("folder") + row.get("subfolder") + "/" + row.get("file_name");
            var fullPath = dataConfig.resize_url.replace(/--IMAGE--/, path);
            var imageTag = $('<img/>').attr('src', $('<div/>').html(fullPath).text());
            dataConfig.grid.getGridView().clearSelectedRows();
            $(textBtnAddImage).each(function () {
                if ($(this).hasClass('active')) {
                    processImage(this, {
                        text: dataConfig.text_change_image,
                        display: 'block',
                        image_tag: imageTag,
                        path: path
                    });
                    $(this).parents('.form-group').find(textBtnAddImage).addClass('data-change');
                }
            })
        });
    };

    /**
     * store multi ajax cache
     * @type {{}}
     */
    FCom.Admin.ajaxCacheStorage = {};

    /**
     * call ajax and cache to variable
     * @param url
     * @param callback
     */
    FCom.Admin.ajaxCache = function (url, callback) {
        if (callback === null) {
            delete FCom.Admin.ajaxCacheStorage[url];
        } else if (!FCom.Admin.ajaxCacheStorage[url]) {
            $.ajax(url, {dataType: 'json',
                /**
                 * @param {{}} data
                 * @param {Array }data._eval
                 */
                success: function (data) {
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
    };

    /**
     *
     * @type {{}}
     */
    FCom.Admin.layouts = {};

    /**
     *
     * @param id
     * @param {{}} opt
     * @param {{}} opt.pub
     * @returns {*}
     */
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
    };

    /**
     *
     * @type {{}}
     */
    FCom.Admin.trees = {};
    /**
     * @callback callbackNode
     * @param {{}} node
     */
    /**
     *
     * @param el
     * @param {{lock_flag: Boolean, create_lock: Boolean, on_click: callbackNode, on_dblclick: callbackNode, on_select: callbackNode}} opt
     * @returns {*}
     */
    FCom.Admin.tree = function (el, opt) {
        var expanded = false, el = $(el);

        if (!opt) return FCom.Admin.load('trees', el);

        /**
         *
         * @returns {boolean}
         */
        function checkLock() {
            if (opt.lock_flag && $(opt.lock_flag).get(0).checked) {
                alert('Locked');
                return false;
            }
            return true;
        }

        /**
         *
         * @param node
         * @param errorText
         * @returns {boolean}
         */
        function checkRoot(node, errorText) {
            if (parseInt($(node).attr('id')) <= 1) {
                alert(errorText);
                return false;
            }
            return true;
        }

        /**
         * get modal html
         * @param id
         * @param title
         * @returns {string}
         */
        function bsModalHtml(id, title) {

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

        /**
         * reorder node
         * @param node
         */
        function reorder(node) {
            FCom.Admin.log('node', node);
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

            /**
             * use bootstraps modal box
             * @type {string}
             */
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

        /**
         * clone node
         * @param node
         */
        function clone(node) {
            if (!checkLock()) return;
            if (!checkRoot(node, 'Cannot clone ROOT')) return;

            /**
             * submit post data
             * @param recursive
             */
            function postClone(recursive) {
                $.post(
                    opt.url,
                    {
                        operation: 'clone',
                        id: $(node).attr("id").replace("node_", ""),
                        recursive: recursive
                    },
                    /**
                     * success callback function
                     * @param {{newNodeID:Number}} r node model
                     */
                    function (r) {
                        if (!r.status) {
                            $.bootstrapGrowl("Error:<br>" + r.message, {type: 'danger', align: 'center', width: 'auto', delay: 5000});
                        } else {
                            el.jstree('refresh', $.jstree._focused()._get_parent(), {idNode: r.newNodeID});
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
                            el.jstree('deselect_all').trigger('deselect_all.jstree');
                            $(el).jstree('select_node', "#node_" + r.id, true);
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
            .bind('refresh.jstree', function (e, data) {
                var obj = data.args[1];
                if (typeof (obj) !== 'undefined' && typeof (obj.idNode) !== 'undefined') {
                    $(el).jstree('deselect_all').trigger('deselect_all.jstree');
                    $(el).jstree('select_node', '#' + obj.idNode, true);
                    var focused = $.jstree._focused();
                    focused.data.ui.to_select = [];
                }
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
                FCom.Admin.log(dd, $(dd.drag).parents('.grid-container'));
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
                        FCom.Admin.log(r);
                    }
                );

                dd.grid.invalidate();
                dd.grid.setSelectedRows([]);
            }
        });

        FCom.Admin.save('trees', el);

        /**
         * toggle expand node
         */
        function toggleExpand() {
            $('#1 li', el).each(function (idx, li) {
                FCom.Admin.log(idx, li);
                el.jstree('toggle_node', li);
            });
        }

        return {toggleExpand: toggleExpand};
    };

    /**
     *
     * @type {{}}
     */
    FCom.Admin.forms = {};

    /**
     * init form for tab content
     * @param {{}} options
     * @param {{}} options.on_tab_load
     * @param {String} options.panes
     * @param {String} options.url_post
     * @returns {{setOptions: setOptions, loadTabs: loadTabs, wysiwygInit: wysiwygInit, wysiwygCreate: wysiwygCreate, wysiwygDestroy: wysiwygDestroy, createSwitchButton: createSwitchButton, tabClass: tabClass, tabAction: tabAction, saveAll: saveAll, deleteForm: deleteForm}}
     */
    FCom.Admin.form = function (options) {
        /* options = {
            tabs:'.adm-tabs-left li',
            panes:'.adm-tabs-content',
            url_get: '.../form_tab/:id',
            url_post: '.../edit/:id'
        } */
        var tabs, panes, curLi, curPane, editors = {};
        var ajaxPassed = false;
        var loader;

        /**
         *
         * @param data
         */
        function loadTabs(data) {
            for (var i in data.tabs) {
                $('#tab-' + i).html(data.tabs[i]).data('loaded', true);
                if (typeof options.on_tab_load !== 'undefined') {
                    options.on_tab_load.apply($('#tab-' + i));
                }
            }
        }

        /**
         * convert textarea to wysiwyg with ckeditor
         * @param {string} id
         * @param {string} value
         * @param callback
         */
        function wysiwygCreate(id, value, callback) {
            if (!editors[id] && CKEDITOR !== 'undefined' && !CKEDITOR.instances[id]) {
                //FCom.Admin.log(id, 'wysiwygcreate');
                editors[id] = true; // prevent double loading

                CKEDITOR.replace(id, {
                    /*toolbarGroups: [
                        { name: 'mode' },
                        { name: 'basicstyles' },
                        { name: 'links' }
                    ],*/
                    filebrowserBrowseUrl: FCom.base_href+'media',
                    //allowedContent: true,
                    startupMode: 'wysiwyg'
                });

                if (value) CKEDITOR.instances[id].setData(value);

                CKEDITOR.instances[id].on('change', function (e) {
                    e.editor.updateElement();
                    if (typeof callback === 'function') {
                        callback(e.editor, e.editor.getData());
                    } else if (typeof callback === 'string') {
                        window[callback](e.editor, e.editor.getData());
                    }
                });
//
//                $('#'+id).ckeditor(function() {
//                    this.dataProcessor.writer.indentationChars = '  ';
//                    editors[id] = this;
//                });
            }
        }

        /**
         * this function almost use to init ckeditor after load ajax form
         */
        function wysiwygInit(target, value, callback) {
            var form = this;
            if (!target) target = 'textarea.ckeditor';
            $(target).each(function () {
                var id = $(this).attr('id');
                if (!id) {
                    var cntEditors = editors.length;
                    id = cntEditors + 1;
                    $(this).attr('id', 'textarea-ckeditor-' + id);
                }
                form.wysiwygCreate(id, value, callback);
            });
        }

        /**
         * destroy ckeditor instance
         * @param id
         */
        function wysiwygDestroy(id) {
            if (editors[id]) {
                try {
                    editors[id].destroy();
                } catch (e) {
                    CKEDITOR.instances[id].destroy();
                    //editors[id].destroy();
                }
                editors[id] = null;
            }
        }

        /**
         * create switch button
         * @peprecated
         */
        function createSwitchButton() { //todo: add options class to add switch button
            $('.switch-cbx').each(function () {
                if ($(this).parents('.make-switch').hasClass('make-switch') == false) {
                    $(this).wrap("<div class='make-switch switch' data-off-label='&lt;i class=\"icon-remove\"&gt;&lt;/i&gt;' data-on-label='&lt;i class=\"icon-ok\"&gt;&lt;/i&gt;' data-on='primary'>").parent().bootstrapSwitch();
                }
            });
        }

        /**
         * process tab class
         * @param id
         * @param cls
         */
        function tabClass(id, cls) {
            var tab = $('a[href=#tab-' + id + ']', tabs).parent('li');
            tab.removeClass('dirty error');
            if (cls) tab.addClass(cls);
        }

        /**
         *
         * @param action
         * @param el
         * @returns {boolean}
         */
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
                    $('a[href=#' + tabId + ']', tabs).removeClass('changed');
                    break;
            }
            return false;
        }

        function _processSessionTimeout(event, data, el, saveAndContinue) {
            if ($.inArray(event.status, [401, 403]) || data.error == 'login') {
                $.get(options.url_get, function(data) {
                    if (data.form !== undefined) {
                        if ($('#login_modal_form').length == 0) {
                            $('body').append(data.form);
                            $('#login_modal_form').modal({keyboard: false}).on('hidden.bs.modal', function() {
                                loader.stop();
                                $(this).data('bs.modal', null).remove();
                            });
                        }
                        $('#login_modal_form_btn').click(function() {
                            $('#login-form').on('login:modal_form:result', function() {
                                $("#login_modal_form").modal('hide');
                                saveAll(el, saveAndContinue);
                            }).submit();
                        });
                    }
                });
            }
        }

        /**
         *
         * @param el
         * @param saveAndContinue
         * @returns {boolean}
         */
        function saveAll(el, saveAndContinue) {
            ajaxPassed = true;
            var form = $(el).closest('form');
            $(form).submit(function(event) {
                if (ajaxPassed) {
                    event.preventDefault();
                }
            });

            loader = Ladda.create(el);
            $(form).trigger('submit');
            if (!form.validate().checkForm()) {
                loader.stop();
                return false;
            }

            loader.start();
            var btnId = form.attr('id') + '-do';
            var isNew = options.is_new;
            if (saveAndContinue) {
                form.append('<input id="' + btnId + '" type="hidden" name="do" value="save_and_continue">');
            }
            var postData = form.serializeArray();
            var url_post = options.url_get + (options.url_post.match(/\?/) ? '&' : '?');
            $.post(url_post + 'tabs=ALL&mode=view', postData, function (data, status, req) {
                FCom.Admin.log(data);
                if (typeof data == 'string') {
                    sysMessages.push({
                        msg: data,
                        type: 'error'
                    });
                    loader.stop();
                } else {
                    if (data.error == 'login') {
                        _processSessionTimeout({status: status}, data, el, saveAndContinue);
                        return;
                    }

                    for (var msgId in data.messages) {
                        sysMessages.push({
                            msg: data.messages[msgId].text || 'The form has been saved',
                            type: data.status == 'error' ? 'error' : 'success'
                        });
                    }
                    if (data.redirect) {
                        document.location = data.redirect;
                    } else {
                        loader.stop();
                        var actionUrl = form.attr('action');
                        var urlInfo = actionUrl.split('?');
                        if (urlInfo[1]) {
                            var params = urlInfo[1].split('&');
                            var newParams = [];
                            for (var paramId = 0; paramId < params.length; paramId++) {
                                var pair = params[paramId].split('=');
                                if (pair[0] == 'id') {
                                    pair[1] = data.id;
                                }
                                newParams.push(pair.join('='));
                            }
                            actionUrl = urlInfo[0] + '?' + newParams.join('&');
                        } else {
                            actionUrl = urlInfo[0] + '?id=' + data.id;
                        }
                        options.url_get = actionUrl;
                        form.attr('action', actionUrl);
                        if (isNew && saveAndContinue && data.status == 'success') {
                            if (window.history !== undefined) {
                                window.history.replaceState({}, data.title, options.url_get);
                            }
                            //TODO: Should not use `find('.btn-group')` because it's very general, We need more details here
                            //form.find('.btn-group').html(data.buttons);
                            var textNodes = form.find('.f-page-title').contents().filter(function () {
                                return this.nodeType == 3;
                            });
                            textNodes[textNodes.length-1].nodeValue = data.title;
                            $('title').text(data.title);
                            form.find('#tabs li.hidden').removeClass('hidden');
                        }
                        $('#' + btnId).remove();
                        $('#tabs .icon-pencil, #tabs .icon-warning-sign.error').remove();
                        ajaxPassed = false;
                    }
                }
            }).fail(function(event, data) {
                loader.stop();
                _processSessionTimeout(event, data, el, saveAndContinue);
            });
            return false;
        }

        /**
         *
         * @param el
         * @returns {boolean}
         */
        function deleteForm(el) {
            if (!confirm('Are you sure?')) return false;
            var form = $(el).parents('form');
            $('input[name=_delete]', form).val(1);
            return true;
        }

        /**
         *
         * @param newOpt
         * @returns {FCom.Admin.setOptions}
         */
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
    };

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

    /**
     *
     * @type {{}}
     */
    FCom.Admin.jqGrid = {};
    /**
     *
     * @param cellvalue
     * @param options
     * @param rowObject
     * @returns {*}
     */
    FCom.Admin.jqGrid.fmtHiddenInput = function (cellvalue, options, rowObject) {
        FCom.Admin.log(cellvalue, options, rowObject);
        // do something here
        return cellvalue ? cellvalue : '';
    };

    /**
     *
     * @param val
     * @param opt
     * @param obj
     * @returns {string}
     */
    FCom.Admin.jqGrid.fmtNewWindow = function (val, opt, obj) {
        return "<a href='javascript:window.open(\"" + val + "\", \"vendor_website_url\", \"width=800,height=600\")'>" + val + "</a>";
    };

    /**
     *
     * @param val
     * @param {{}} opt
     * @param {{inputName: String}} opt.colModel
     * @param {String} opt.gid
     * @param obj
     * @returns {string}
     */
    FCom.Admin.jqGrid.fmtRadioButton = function (val, opt, obj) {
        var id = opt.colModel.inputId || opt.gid + '-' + opt.colModel.name + '-' + val;
        var name = opt.colModel.inputName || opt.gid + '[' + opt.colModel.name + ']';
        return '<input type="radio" id="' + id + '" name="' + name + '" value="' + val + '"/>';
    };

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

    /**
     *
     * @type {{}}
     */
    FCom.Admin.codeEditorThemeLoaded = {};

    /**
     * init code editors
     */
    FCom.Admin.initCodeEditors = function () {
        if (typeof CodeMirror == 'undefined') return;

        var scriptBaseUrl = FCom.Admin.code_mirror_base_url;

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
            var editor = CodeMirror.fromTextArea(el, options);
            if (mode) {
                //editor.setOption('mode', mode);
                CodeMirror.autoLoadMode(editor, mode);
            }
            $(el).data('editor', editor);
        });
    };

    /**
     * Temporary fix for modal validation
     *
     * @param container
     * @returns {boolean}
     *
     */
    $.fn.modalValidate = function (container) {
        if (!container.parent('form').length) {
            container.wrap('<form>');
        }

        if (!container.parent('form').valid()) return false;

        container.unwrap();

        return true;
    };

    /**
     * Deep clone Object|Array|Date
     *
     * @param obj
     * @returns {*}
     */
    $.fn.deepClone = function (obj) {
        var copy;

        // Handle the 3 simple types, and null or undefined
        if (null == obj || "object" != typeof obj) return obj;

        // Handle Date
        if (obj instanceof Date) {
            copy = new Date();
            copy.setTime(obj.getTime());
            return copy;
        }

        // Handle Array
        if (obj instanceof Array) {
            copy = [];
            for (var i = 0, len = obj.length; i < len; i++) {
                copy[i] = $.fn.deepClone(obj[i]);
            }
            return copy;
        }

        // Handle Object
        if (obj instanceof Object) {
            copy = {};
            for (var attr in obj) {
                if (obj.hasOwnProperty(attr)) copy[attr] = $.fn.deepClone(obj[attr]);
            }
            return copy;
        }

        throw new Error("Unable to copy obj! Its type isn't supported.");
    };

    $.fn.setValidateForm = function (selector) {
        if (selector == null) {
            selector = $(".validate-form");
        }
        if ($().validate) {
            return selector.each(function (i, elem) {
                return $(elem).validate({
                    errorElement: "span",
                    errorClass: "help-block has-error",
                    errorPlacement: function (e, t) {
                        return t.parents(".controls").first().append(e);
                    },
                    highlight: function (e) {
                        $(e).closest('.form-group').removeClass("has-error has-success").addClass('has-error');
                        return $(e).closest('span.help-block').css('display', 'block');
                    },
                    success: function (e) {
                        e.closest(".form-group").removeClass("has-error");
                        return e.closest("span.help-block").css('display', 'none');
                    }
                });
            });
        }
    };

    /**
     * resize width window
     * @param options
     */
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
    };

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
            CKEDITOR.config.filebrowserWindowHeight = 757;
            CKEDITOR.config.filebrowserWindowWidth = 912;
            //CKEDITOR.config.allowedContent = true;

            //CKEDITOR.config.filebrowserUploadUrl = '/';
        }
        //$('.datepicker').datepicker();
        $(document).bind('ajaxSuccess', function (event, request, settings) {
            if (request.responseText[0]==='{' && (data = $.parseJSON(request.responseText))) {
                if (data.error == 'login') {
//                    location.href = FCom.base_href;
                    //location.reload(true);
                }
            }
        });
        $('header .navbar .toggle-nav').click(function (ev) {
            var postData = { do: 'nav.collapse', collapsed: $('body').hasClass('main-nav-closed') ? 1 : 0 };
            $.post(FCom.Admin.personalize_href, postData, function (response, status, xhr) {
                FCom.Admin.log(response);
            });
        });
        $('.nav-group header').click(function (ev) {
            $(ev.currentTarget).parent('li').find('ul').animate({
                opacity: 'toggle',
                height: 'toggle'
            }, 100);
        });

        FCom.Admin.initCodeEditors();

        $('.js-resizable').each(function (idx, el) {
            $(el).resizable();
        });

        $.fn.foundationCustomForms && $(".foundation-forms").foundationCustomForms();
    })
});
