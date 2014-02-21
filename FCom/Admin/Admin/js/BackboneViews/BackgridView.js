define(['backbone', 'marionette', 'pageableCollection', 'textCell', 'paginator', 'filter', 'lunr', 'jqDragtable'],
    function (Backbone, Marionette, PageableCollection) {
        var BackgridView = Backbone.Marionette.ItemView.extend({
            template: '#gridTemplate',
            backgridOptions: {},
            container: null,
            collection: null,
            grid: null,
            state: { pageSize: 15 },
            initialize: function (options) {
                this.backgridOptions = _.defaults(options, {
                    resizable: false,
                    dragable: false,
                    filters: {},
                    pageable: false,
                    data_mode: 'server',
                    state: { pageSize: 15 },
                    container: '#grid'
                });
            },
            prepareConfig: function () {
                _.map(this.options.columns, function (col, i) {
                    if (!col.name) {
                        col.name = '';
                    }
                    if (!col.cell) {
                        col.cell = 'string';
                    }
                    return col;
                });
            },
            setResize: function (resizable) {
                self = this;
                if (resizable) {
                    self.grid.$('thead th').resizable({
                        handles: 'e',
                        minWidth: 20,
                        resize: function (ev, ui) {
                            self.grid.$el.get(0).className = self.grid.$el.get(0).className; //reflow
                        },
                        stop: function (ev, ui) {
                            var $el = ui.element, width = $el.width();
                            $.post(self.options.personalize_url,
                                { 'do': 'grid.col.width', grid: self.options.id, col: $el.data('id'), width: width },
                                function (response, status, xhr) {
                                    //console.log(response, status, xhr);
                                }
                            )
                        }
                    });
                }
            },
            setDragable: function (dragable) {
                self = this;
                if (dragable) {
                    self.grid.$el.dragtable({
                        scroll: true,
                        appendParent: self.grid.$el, //jebaird
                        items: 'thead .drag-handle', //jebaird
                        handle: 'drag-handle', //jebaird
                        change: function () { //jebaird
                            //console.log($('.dragtable-drag-wrapper').html());
                        },
                        stop: function () { //jebaird
                            var cols = [];
                            self.grid.$('thead th').each(function (i, el) {
                                cols.push({ name: $(el).data('id') });
                            });
                            $.post(self.options.personalize_url,
                                { 'do': 'grid.col.order', grid: self.options.id, cols: JSON.stringify(cols) },
                                function (response, status, xhr) {
                                    //console.log(response, status, xhr);
                                }
                            );
                        }
                    });
                }
            },
            setPagination: function () {
                // var FulleronPaginator = Backgrid.Extension.Paginator.extend({
                // windowSize: 10, // Default is 10
                // hasFastForward: true, // true is the default
                // fastForwardHandleLabels: {
                // prev: "<",
                // next: ">"
                // }
                // });    TODO create type of paginators

                var paginator = new Backgrid.Extension.Paginator({
                    collection: this.collection
                });
                this.container.append(paginator.render().el);
            },
            getPagableCollection: function (Model) {
                var self = this;
                var invDirs = {'asc': '-1', 'desc': '1'};
                var paramMap = {
                    currentPage: 'p',
                    pageSize: 'ps',
                    totalPages: 'mp',
                    totalRecords: 'c',
                    sortKey: 's',
                    order: 'sd',
                    directions: {
                        '-1': 'asc',
                        '1': 'desc'
                    }
                };
                _.each(paramMap, function (k, v) {
                    if (self.backgridOptions.state[k]) {
                        var val = self.options.state[k];
                        if ('order' === v) {
                            val = invDirs[val];
                        }
                        self.state[v] = val;
                    }
                });
                debugger;
                var Collection = PageableCollection.extend({
                    model: Model,
                    url: self.backgridOptions.data_url,
                    state: self.state,
                    mode: self.backgridOptions.data_mode
                    //queryParams: paramMap
                });
                var collection = new Collection();
                if (self.backgridOptions.collection) {
                    collection.set(self.backgridOptions.collection);
                }
                return collection;
            },
            getCollection: function () {
                var Model = this.options.model || Backbone.Model;
                if (this.backgridOptions.data_url || this.backgridOptions.pageable) {
                    var collection = this.getPagableCollection(Model);
                } else {
                    var collection = Backbone.Collection.extend({
                        model: Model
                    });
                    var collection = new Collection(this.backgridOptions.collection);
                }
                return collection;
            },
            setFilters: function (){},
            onRender: function () {

                var self = this;
                this.container = $(this.options.container);
                this.prepareConfig();
                this.collection = this.getCollection();

                this.grid = new Backgrid.Grid({
                    columns: this.options.columns,
                    collection: this.collection
                });
                debugger;
                this.container.append(this.grid.render().$el);
                this.setResize(this.backgridOptions.resizable);
                this.setDragable(this.backgridOptions.dragable);
                //this.setFilters(this.backgridOptions.filters);
                this.setPagination(this.backgridOptions.pageable);

                if (this.options.data_url) {
                    this.collection.fetch({reset: true});
                }

            }
        });
        return BackgridView;
    }
);
