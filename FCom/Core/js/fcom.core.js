define(["jquery", "backbone", "backbone-pageable", "lunr", "transparency",
    "backgrid", "backgrid-filter", "backgrid-select-all",
    "backgrid-paginator", "backgrid-select-all", "backgrid-moment-cell"],

function($, Backbone, PageableCollection) {
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

        // update URL and load grid partial
        function load(url) {
            if (url) {
                config.grid_url = url;
            }
            gridParent.load(config.grid_url, function(response, status, xhr) {
                initDOM();
            })
        }

        // set multiselect selections
        function setSelection(selection)
        {
            var sel = selection || gridSelection;
            for (var i in sel) {
                $('tbody tr[data-id='+i+'] .js-sel', gridParent).prop('checked', sel[i]);
                gridSelection[i] = sel[i];
            }
            $('#'+config.id+'-selection').val( Object.keys(gridSelection).join('|') );
        }

        // initialize DOM after loading partial
        function initDOM() {
            // initialize unsaved selections
            setSelection();

            var $table = $('table.fcom-datagrid__grid', gridParent);

            // resize columns
            $('thead th', gridParent).resizable({
                handles: 'e',
                minWidth: 20,
                stop: function(ev, ui) {
                    var $el = ui.element, width = $el.width();
                    //$('tbody td[data-col="'+$el.data('id')+'"]', gridParent).width(width);
                    $.post(config.personalize_url,
                        { 'do': 'grid.col.width', grid: config.id, col: $el.data('id'), width: width },
                        function(response, status, xhr) {
                            //console.log(response, status, xhr);
                        }
                    )
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

            $table.dragtable({
                handle: 'drag-handle',
                items: 'thead .drag-handle',
                scroll: true,
                appendParent: $table,
                change: function() {
                    //console.log($('.dragtable-drag-wrapper').html());
                },
                stop: function() {
                    var cols = [];
                    $('thead th', gridParent).each(function(i, el) {
                        cols.push({ name: $(el).data('id') });
                    });
                    $.post(config.personalize_url,
                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
                        function(response, status, xhr) {
                            //console.log(response, status, xhr);
                        }
                    );
                }
            });

            /*
            $('thead', gridParent).sortable({
                items: 'th',
                containment:'parent',
                update: function(ev, ui) {
                    var cols = [];
                    $('th', this).each(function(i, el) {
                        cols.push({ name: $(el).data('id') });
                    });
                    $.post(config.personalize_url,
                        { 'do': 'grid.col.order', grid: config.id, cols: JSON.stringify(cols) },
                        function(response, status, xhr) {
                            console.log(response, status, xhr);
                            if (response.success) {
                                load();
                            }
                        }
                    );
                }
            });
            */
        }
        // initialize DOM first time on page load
        initDOM();

            // handle toolbar and pager selects and inputs
        gridParent.on('change', 'select.js-change-url, input.js-change-url', function(ev) {
            load( $(this).data('href').replace('-VALUE-', this.value) );
        });

        // handle sort labels
        gridParent.on('click', 'a.js-change-url', function(ev) {
            load( this.href );
            return false;
        });

        // handle grid top multiselect toggle
        gridParent.on('change', 'thead select.js-sel', function(ev) {
            var action = this.value, $cb;
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
                    $('tbody input.js-sel', gridParent).each(function(idx, el) {
                        $cb = $(this);
                        sel[ $cb.parents('tr').data('id') ] = action === 'upd_sel';
                    });
                    setSelection(sel);
                    break;
            }
            $(this).val('');
        });

        // handle each row multiselect toggles
        gridParent.on('change', 'tbody input.js-sel', function(ev) {
            var $cb = $(this), sel = {};
            sel[ $cb.parents('tr').data('id') ] = $cb.prop('checked');
            setSelection(sel);
        });

        // handle each row actions
        gridParent.on('change', 'tbody select.js-actions', function(ev) {
            var $select = $(this), $option = $($('option', $select).get(this.selectedIndex));
            var data = $option.data();
            if (data.href) {
                location.href = data.href;
            } else if (data.eval) {
                eval(data.eval);
            }
        });
    }

    if (typeof Backbone !== 'undefined') {
        FCom.TransparencyView = Backbone.View.extend({
            constructor: function() {
                Backbone.View.prototype.constructor.apply(this, arguments);
                this.setElement($(this.options.baseEl).clone());
                this.model.on("change", this.render, this);
            },

            render: function() {
                Transparency.render(this.el, this.model.toJSON());
            }
        });

        Backgrid.Column.prototype.defaults.editable = false;

        Backgrid.Column.prototype.defaults.headerCell = Backgrid.HeaderCell.extend({
            render: function() {
                Backgrid.HeaderCell.prototype.render.apply(this, arguments);
                this.$el.width(this.column.get('width'));
                this.$el.attr('data-id', this.column.get('name'));
                var dragHandle = $('<div class="drag-handle">');
                this.$el.append(dragHandle);
                return this;
            }
        });
        Backgrid.Extension.SelectRowCell.prototype.render = function() {
            this.$el.empty().append('<input tabindex="-1" type="checkbox" />');
            this.delegateEvents();
            this.$el.width(this.column.get('width')); //ADDED
            return this;
        }

        FCom.Backgrid = {};

        //FCom.Backgrid.

        FCom.Backgrid.HrefCell = Backgrid.Cell.extend({
          /** @property */
          className: "href-cell",

          render: function () {
            this.$el.empty();
            var formattedValue = this.formatter.fromRaw(this.model.get(this.column.get("name")));
            var href = _.template(this.column.get('href'))(this.model);
            this.$el.append($("<a>", {
              tabIndex: -1,
              href: href,
              title: formattedValue
            }).text(formattedValue));
            this.delegateEvents();
            return this;
          }

        });

        FCom.Backgrid.Toolbar = Backbone.View.extend({
            className: 'fcom-backgrid-toolbar',

            constructor: function(options) {
                Backbone.View.prototype.constructor.apply(this, arguments);
                this.setElement($(options.template).clone());
                this.model.on('change', this.render, this);
            },

            templateData: function() {

            },

            render: function() {
                var template = $(this.options.template);
                this.$el.empty();
                if (template.length) {
                    this.$el.append($(this.options.template).html()).render(this.templateData());
                }
                this.delegateEvents();
                return this;
            }
        })

        FCom.BackgridView = Backbone.View.extend({
            prepareConfig: function() {
                _.map(this.options.columns, function(col, i) {
                    if (!col.name) col.name = '';
                    if (!col.cell) col.cell = 'string';
                    return col;
                });
            },

            render: function() {
                var self = this, paginator, filter;

                this.prepareConfig();

                var Model = this.options.model || Backbone.Model;

                if (this.options.data_url) {
                    var Collection = PageableCollection.extend({
                        model: Model,
                        url: this.options.data_url,
                        state: this.options.state || { pageSize: 25 },
                        mode: this.options.data_mode || 'server',
                        queryParams: {
                            currentPage: 'p',
                            pageSize: 'ps',
                            totalPages: 'mp',
                            totalRecords: 'c',
                            sortKey: 's',
                            order: 'sd',
                            directions: { 'asc':'asc', 'desc':'desc' }
                        }
                    });
                    var collection = new Collection();
                    /*
                    paginator = new Backgrid.Extension.Paginator({
                        collection: collection
                    });

                    filter = new Backgrid.Extension.ServerSideFilter({
                        collection: collection,
                        fields: ['name']
                    });
                    */
                } else {
                    var Collection = Backbone.Collection.extend({
                        model: Model
                    })
                    var collection = new Collection(this.options.collection);
                }

                var toolbarOptions = this.options.toolbar;
                toolbarOptions.columns = this.options.columns;
                toolbarOptions.collection = collection;
                var toolbar = new FCom.Backgrid.Toolbar(toolbarOptions);

                var grid = new Backgrid.Grid({
                    columns: this.options.columns,
                    collection: collection
                });


                var $container = $(this.options.container);

                if (toolbar) {
                    $container.append(toolbar.render().$el);
                }

                if (false && filter) {
                    $container.append(filter.render().$el);
                }

                $container.append(grid.render().$el);

                if (paginator) {
                    $container.append(paginator.render().$el);
                }
                if (this.options.data_url) {
                    collection.fetch({ reset:true });
                }

                if (true) { // true = jquery-ui resizable, false = colResizable
                    grid.$('thead th').resizable({
                        handles: 'e',
                        minWidth: 20,
                        resize: function(ev, ui) {
                            grid.$el.get(0).className = grid.$el.get(0).className; //reflow
                        },
                        stop: function(ev, ui) {
                            var $el = ui.element, width = $el.width();
                            //$('tbody td[data-col="'+$el.data('id')+'"]', gridParent).width(width);
                            $.post(self.options.personalize_url,
                                { 'do': 'grid.col.width', grid: self.options.id, col: $el.data('id'), width: width },
                                function(response, status, xhr) {
                                    //console.log(response, status, xhr);
                                }
                            )
                        }
                    });
                } else { // interferes with dragtable
                    grid.$el.colResizable();
                }

                if (true) { // true = jebaird, false = akottr
                    grid.$el.dragtable({
                        scroll: true, //jebaird
                        appendParent: grid.$el, //jebaird
                        items: 'thead .drag-handle', //jebaird
                        handle: 'drag-handle', //jebaird
                        change: function() { //jebaird
                            //console.log($('.dragtable-drag-wrapper').html());
                        },
                        stop: function() { //jebaird
                            var cols = [];
                            grid.$('thead th').each(function(i, el) {
                                cols.push({ name: $(el).data('id') });
                            });
                            $.post(self.options.personalize_url,
                                { 'do': 'grid.col.order', grid: self.options.id, cols: JSON.stringify(cols) },
                                function(response, status, xhr) {
                                    //console.log(response, status, xhr);
                                }
                            );
                        }
                    });
                } else { // akottr looks better and works faster, but interferes with resizable or colResizable
                    grid.$el.dragtable({
                        dragHandle: '.drag-handle', //akottr
                        dragAccept: '', //akottr
                        persistState: function() { //akottr
                            var cols = [];
                            grid.$('thead th').each(function(i, el) {
                                cols.push({ name: $(el).data('id') });
                            });
                            $.post(self.options.personalize_url,
                                { 'do': 'grid.col.order', grid: self.options.id, cols: JSON.stringify(cols) },
                                function(response, status, xhr) {
                                    //console.log(response, status, xhr);
                                }
                            );
                        }
                    });
                }
            }
        })
    }

    $(function() {
        $('form').append($('<input type="hidden" name="X-CSRF-TOKEN"/>').val(csrfToken));
        if ($.fn.select2) {
            $('.select2').select2({width:'other values', minimumResultsForSearch:20, dropdownAutoWidth:true});
        }
    })

})
