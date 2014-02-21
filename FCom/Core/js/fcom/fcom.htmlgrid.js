define(['backbone', 'underscore', 'jquery', 'jquery.cookie', 'jquery.tablesorter', 'jquery.dragtable'], function (Backbone, _, $) {

    var BackboneGrid = {
        Models: {},
        Collections: {},
        Views: {},
        currentState: {}
    }

    BackboneGrid.Models.ThModel = Backbone.Model.extend({
        defaults: {
            style: '',
            type: '',
            no_reorder: false,
            sortState: '',
            cssClass: ''
        },
        url: function () {
            return this.personalize_url;
        }

    });

    BackboneGrid.Collections.ThCollection = Backbone.Collection.extend({
        model: BackboneGrid.Models.ThModel
    });

    BackboneGrid.Views.ThView = Backbone.View.extend({
        events: {
            'click a': 'changesortState'
        },
        initialize: function () {
            this.model.on('change', this.render, this);
            this.setElement($(this.template(this.model.toJSON())));

        },
        changesortState: function (ev) {

            var status = this.model.get('sortState');

            if (status === '')
                status = 'asc';
            else if (status === 'asc')
                status = 'desc';
            else
                status = '';
            this.model.set('sortState', status);

            columnsCollection.each(function (m) {
                if (m.get('sortState') !== '' && m.get('name') !== this.model.get('name')) {
                    m.set('sortState', '');
                }
            }, this);

            BackboneGrid.currentState.s = status !== '' ? this.model.get('name') : '';
            BackboneGrid.currentState.sd = this.model.get('sortState');

            rowsCollection.fetch({reset: true});

            //this.model.save();

            ev.preventDefault();
            return false;

        },
        render: function () {

            this.$el.attr('class', this.model.get('cssClass') + ' sort-' + this.model.get('sortState'));

            return this;
        }
    });

    BackboneGrid.Views.HeaderView = Backbone.View.extend({
        render: function () {
            this.collection.each(this.addTh, this);
            return this;
        },
        addTh: function (thModel) {
            var th = new BackboneGrid.Views.ThView({
                model: thModel
            });
            this.$el.append(th.render().el);
        }
    });
    BackboneGrid.Models.Row = Backbone.Model.extend({
        defaults: {
            _actions: '',
            description: '',
            version: '',
            run_status: '',
            run_level: '',
            run_level_core: '',
            requires: '',
            required_by: ''
        }
    });

    BackboneGrid.Collections.Rows = Backbone.Collection.extend({
        model: BackboneGrid.Models.Row,
        url: function () {
            var append = '';
            _.each(BackboneGrid.currentState, function (v, k, l) {
                if (append != '')
                    append += '&';
                append += (k + '=' + v);
            });

            return this.data_url + '?' + append;
        },
        parse: function (response) {
            if (response[0].c) {


                var mp = Math.round(response[0].c / BackboneGrid.currentState.ps);
                /*console.log('c=', response[0].c);
                 console.log('ps=', BackboneGrid.currentState.ps);
                 console.log('mp=', mp);
                 console.log('bb.mp=', BackboneGrid.currentState.mp);*/
                if (mp !== BackboneGrid.currentState.mp) {
                    BackboneGrid.currentState.mp = mp;
                    updatePageHtml();
                }
            }
            return response[1];
        }
    });

    BackboneGrid.Views.RowView = Backbone.View.extend({
        //template: _.template($('#row-template').html()),
        initialize: function () {
            _.templateSettings.variable = "col";
        },
        render: function () {
            this.setElement($(this.template(this.model.toJSON())));
            return this;
        }
    });

    BackboneGrid.Views.GridView = Backbone.View.extend({
        //el: '.fcom-htmlgrid__grid tbody',
        initialize: function () {
            this.collection.on('reset', this.beforeRender, this);
        },
        beforeRender: function () {
            var models = this.collection.models;
            for (var i in models) {
                var cssClass = i % 2 == 0 ? 'even' : 'odd';
                models[i].set('cssClass', cssClass);
            }

            this.render();
        },
        render: function () {
            this.$el.html('');
            this.collection.each(this.addRow, this);

            this.$el.parents('table:first').dragtable({dragHandle: '.draghandle'});
            return this;
        },
        addRow: function (row) {
            var rowView = new BackboneGrid.Views.RowView({
                model: row
            });
            this.$el.append(rowView.render().el);
        }
    });

    function updatePageHtml(p, mp) {

        p = BackboneGrid.currentState.p;
        mp = BackboneGrid.currentState.mp;
        var html = '';

        html += '<li class="first' + (p <= 1 ? ' disabled' : '') + '">';
        html += '<a class="js-change-url" href="#">&laquo;</a>';
        html += '</li>';

        html += '<li class="prev' + (p <= 1 ? ' disabled' : '') + '">';
        html += '<a class="js-change-url" href="#">&lsaquo;</a>';
        html += '</li>';


        for (var i = Math.max(p - 3, 1); i <= Math.min(p + 3, mp); i++) {
            html += '<li class="page' + (i == p ? ' active' : '') + '">';
            html += '<a class="js-change-url" data-page="" href="#">' + i + '</a>';
            html += '</li>';
        }

        html += '<li class="next' + (p >= mp ? ' disabled' : '') + '">';
        html += '<a class="js-change-url" href="#">&rsaquo;</a>';
        html += '</li>';

        html += '<li class="last' + (p >= mp ? ' disabled' : '') + '">';
        html += '<a class="js-change-url" href="#">&raquo;</a>';
        html += '</li>';

        $('ul.pagination.page').html(html);
    }

    var rowsCollection;
    var columnsCollection;

    FCom.BackboneGrid = function (config) {

        //Theader
        BackboneGrid.Models.ThModel.prototype.personalize_url = config.personalize_url;

        BackboneGrid.Views.ThView.prototype.template = _.template($('#' + config.headerTemplate).html());
        BackboneGrid.Views.HeaderView.prototype.el = "#" + config.id + " thead tr";
        //Tbody
        BackboneGrid.Views.GridView.prototype.el = "#" + config.id + " tbody";
        BackboneGrid.Views.RowView.prototype.template = _.template($('#' + config.rowTemplate).html());
        BackboneGrid.Collections.Rows.prototype.data_url = config.data_url;
        //pager
        //BackboneGrid.Views.PageView.prototype.template = _.template($('#'+config.pageTemplate).html());
        var state = config.data.state;


        state.p = parseInt(state.p);
        state.mp = parseInt(state.mp);
        BackboneGrid.currentState = state;

        $('ul.pagination.page').on('click', 'li', function (ev) {
            var li = $(this);
            if (li.hasClass('first'))
                BackboneGrid.currentState.p = 1;
            if (li.hasClass('next'))
                BackboneGrid.currentState.p++;
            if (li.hasClass('prev'))
                BackboneGrid.currentState.p--;
            if (li.hasClass('last'))
                BackboneGrid.currentState.p = BackboneGrid.currentState.mp;
            if (li.hasClass('page'))
                BackboneGrid.currentState.p = parseInt(li.find('a').html());
            updatePageHtml();
            rowsCollection.fetch({reset: true});
            ev.preventDefault();
            return;
        });

        updatePageHtml();

        //header view
        var columns = config.columns;
        columnsCollection = new BackboneGrid.Collections.ThCollection;


        for (var i in columns) {
            var c = columns[i];
            if (!c.hidden) {
                c.id = config.id + '-' + c.name;
                c.style = c['width'] ? "width:" + c['width'] + "px" : '';

                c.cssClass = '';
                if (!c['no_reorder'])
                    c.cssClass += 'js-draggable ';

                if (state['s'] && c['name'] && state['s'] == c['name']) {
                    //c.cssClass += 'sort-' + state['sd'] + ' ';
                    c.sortState = state['sd'];
                } else {
                    //c.cssClass += 'sort';
                    c.sortState = "";
                }

                var thModel = new BackboneGrid.Models.ThModel(c);
                columnsCollection.add(thModel);
            }
        }

        var headerView = new BackboneGrid.Views.HeaderView({collection: columnsCollection});
        headerView.render();

        //body view
        var rows = config.data.data;
        rowsCollection = new BackboneGrid.Collections.Rows;

        for (var i in rows) {

            if (i % 2 === 0) {
                rows[i].cssClass = "even";
            } else {
                rows[i].cssClass = "odd";
            }
            var rowModel = new BackboneGrid.Models.Row(rows[i]);
            rowsCollection.add(rowModel);
        }

        var gridView = new BackboneGrid.Views.GridView({collection: rowsCollection});
        gridView.render();

        $('ul.pagination.pagesize a').click(function (ev) {
            $('ul.pagination.pagesize li').removeClass('active');
            BackboneGrid.currentState.ps = parseInt($(this).html());
            BackboneGrid.currentState.p = 1;
            rowsCollection.fetch({reset: true});
            $(this).parents('li:first').addClass('active');
            ev.preventDefault();

            return false;

        });

        //table column reordering

    }
    FCom.HtmlGrid = function (config) {

        var gridEl = $('#' + config.id);
        var gridParent = gridEl.parent();
        var gridSelection = config.selection || {};

        // update URL and load grid partial
        function load(url) {
            if (url) {
                config.grid_url = url;
            }
            gridParent.load(config.grid_url, function (response, status, xhr) {
                initDOM();
            })
        }

        // set multiselect selections
        function setSelection(selection) {
            var sel = selection || gridSelection;
            for (var i in sel) {
                $('tbody tr[data-id=' + i + '] .js-sel', gridParent).prop('checked', sel[i]);
                gridSelection[i] = sel[i];
            }
            $('#' + config.id + '-selection').val(Object.keys(gridSelection).join('|'));
        }

        // initialize DOM after loading partial
        function initDOM() {
            // initialize unsaved selections
            setSelection();

            var $table = $('table.fcom-htmlgrid__grid', gridParent);

            $('.showhide_column').each(function () {
                $(this).attr('checked', 'checked');
            })
            $('.showhide_column').bind('click', function (e) {

                var id = $(this).data('id');

                $('.table-bordered  tr').each(function () {
                    $(this).children('th').each(function () {
                        if ($(this).data('id') == id) {
                            index = $(this).index();
                        }
                    });


                    $('td:eq(' + index + ')', this).toggle();
                    $('th:eq(' + index + ')', this).toggle();
                });
                //$('.dropdown-toggle').dropdown('toggle');
                e.stopPropagation();
            })


            $(".dropdown_menu").sortable({
                revert: true,
            });
//                $( ".dropdown-menu li" ).draggable({
//                    connectToSortable: ".dropdown_menu",
//                    helper: "clone",
//                    revert: "invalid"
//                });
            $("ul, li").disableSelection();


            $(".dropdown-menu").sortable({
                revert: true
            });

            $('.dropdown-menu').droppable({
                drop: function (event, ui) {

                    var cols = [];
                    $('.dropdown-menu').find("input").each(function (i, el) {
                        cols.push({ name: $(el).data('id') });
                    });

                    $.ajaxSetup({ async: false });

                    $.post(config.personalize_url,
                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
                        function (response, status, xhr) {
                            //console.log(response, status, xhr);
                            if (response.success) {
                                load();
                            }
                        }
                    )

                }
            });


            // resize columns

            $('thead th', gridParent).resizable({
                handles: 'e',
                minWidth: 20,
                stop: function (ev, ui) {
                    var $el = ui.element, width = $el.width();
                    //$('tbody td[data-col="'+$el.data('id')+'"]', gridParent).width(width);
                    $.post(config.personalize_url,
                        { 'do': 'grid.col.width', grid: config.id, col: $el.data('id'), width: width },
                        function (response, status, xhr) {
                            //console.log(response, status, xhr);
                        }
                    );
                }
            });
            /*
             $table.colResizable({
             liveDrag: true,
             draggingClass: 'dragging',
             onResize: function(a, b, c) {
             console.log(a, b, c, this); return;
             var $el = ui.element;
             $.post(config.personalize_url,
             { 'do': 'grid.col.width', grid: config.id, col: $el.data('id'), width: $el.width() },
             function(response, status, xhr) {
             //console.log(response, status, xhr);
             }
             )
             }
             });
             */

            // reorder columns

//            $table.dragtable({
//                handle: 'drag-handle',
//                items: 'thead .drag-handle',
//                scroll: true,
//                appendParent: $table,
//                change: function() {
//                    //console.log($('.dragtable-drag-wrapper').html());
//                },
//                stop: function() {
//                    var cols = [];
//                    $('thead th', gridParent).each(function(i, el) {
//                        cols.push({ name: $(el).data('id') });
//                    });
//                    $.post(config.personalize_url,
//                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
//                        function(response, status, xhr) {
//                            //console.log(response, status, xhr);
//                        }
//                    );
//                }
//            });


//            $('thead', gridParent).sortable({
//                items: 'th',
//                containment:'parent',
//                update: function(ev, ui) {
//                    var cols = [];
//                    $('th', this).each(function(i, el) {
//                        cols.push({ name: $(el).data('id') });
//                    });
//                    $.post(config.personalize_url,
//                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
//                        function(response, status, xhr) {
//                            console.log(response, status, xhr);
//                            if (response.success) {
//                                load();
//                            }
//                        }
//                    );
//                }
//            });

        }

        // initialize DOM first time on page load
        initDOM();

        // handle toolbar and pager selects and inputs
        gridParent.on('change', 'select.js-change-url, input.js-change-url', function (ev) {
            load($(this).data('href').replace('-VALUE-', this.value));
        });

        // handle sort labels
        gridParent.on('click', 'a.js-change-url', function (ev) {
            if ($(this).parent().hasClass('disabled')) {
                return false;
            }
            load(this.href);
            return false;
        });

        // handle grid top multiselect toggle
        gridParent.on('change', 'thead select.js-sel', function (ev) {
            var action = this.value, $cb;
            switch (action) {
                case 'show_all':
                    load(setUrlParam(config.grid_url, { selected: false }));
                    break;

                case 'show_sel':
                    load(setUrlParam(config.grid_url, { selected: 'sel' }));
                    break;

                case 'show_unsel':
                    load(setUrlParam(config.grid_url, { selected: 'unsel' }));
                    break;

                case 'upd_sel':
                case 'upd_unsel':
                    var sel = {};
                    $('tbody input.js-sel', gridParent).each(function (idx, el) {
                        $cb = $(this);
                        sel[ $cb.parents('tr').data('id') ] = action === 'upd_sel';
                    });
                    setSelection(sel);
                    break;
            }
            $(this).val('');
        });

        // handle each row multiselect toggles
        gridParent.on('change', 'tbody input.js-sel', function (ev) {
            var $cb = $(this), sel = {};
            sel[ $cb.parents('tr').data('id') ] = $cb.prop('checked');
            setSelection(sel);
        });

        // handle each row actions
        gridParent.on('change', 'tbody select.js-actions', function (ev) {
            var $select = $(this), $option = $($('option', $select).get(this.selectedIndex));
            var data = $option.data();
            if (data.href) {
                location.href = data.href;
            } else if (data.eval) {
                eval(data.eval);
            }
        });


    }
})
