define(['jquery', 'underscore', 'backbone', 'backgrid', 'backbone-pageable', 'exports', 'fcom.core'], function($, _, Backbone, Backgrid, PageableCollection, exports) {
    Backgrid.Column.prototype.defaults.editable = false;

    Backgrid.Column.prototype.defaults.headerCell = Backgrid.HeaderCell.extend({
       render: function() {
            Backgrid.HeaderCell.prototype.render.apply(this, arguments);
            this.$el.width(this.column.get('width'));
            this.$el.attr('data-id', this.column.get('name'));
            var dragHandle = $('<div class="drag-handle"><i class="icon-reorder"></i></div>');
            this.$el.append(dragHandle);
            return this;
        },
      sort:function(colName,dir){
            Backgrid.HeaderCell.prototype.sort.apply(this, arguments);
            Backbone.trigger("backgrid:sort",colName,dir);
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

        bindings: {

        },

        initialize: function() {

            this.model = new Backbone.Model(this.options);
            this.template = _.template($(this.options.template).html());
            var self = this;

            _.each(['page_sizes', 'page_numbers'], function(selectName) {
                var options = self.model.get(selectName);
                if (options) {
                    this.bindings[selectName] = {
                        selector:'[name='+selectName+']',
                        converter: new Backbone.ModelBinder.CollectionConverter(self.model.get(selectName))
                    }
                }
            });

        },

        render: function() {
            //this.modelBinder.bind(this.model, this.el, this.bindings);
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
            if (this.options.data_url || this.options.pageable) {

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
                }
                var state = { pageSize: 25 }, invDirs = {'asc':'-1', 'desc':'1'};
                console.log(self.options.state);
                _.each(paramMap, function(v, k) {
                    var val = self.options.state[v];
                    console.log(k, v, val);
                    if (val) {
                        if ('order' === k) val = val ? invDirs[val] : 1;
                        state[k] = val;
                    }
                });
                //console.log(state);
                var Collection = PageableCollection.extend({
                    model: Model,
                    url: this.options.data_url,
                    state: state,
                    mode: this.options.data_mode || 'client',
                    queryParams: paramMap
                });
                var collection = new Collection();
                if(this.options.collection){
                    collection.set(this.options.collection);
                }
            } else {
                var Collection = Backbone.Collection.extend({
                    model: Model
                });
                var collection = new Collection(this.options.collection);
            }

            /*
            paginator = new Backgrid.Extension.Paginator({
                collection: collection
            });

            filter = new Backgrid.Extension.ServerSideFilter({
                collection: collection,
                fields: ['name']
            });
            */

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

           if(state.sortKey)
            {
                var click_times=0;
                if(state.order=='-1')
                    click_times=1;
                if(state.order=='1')
                    click_times=2;
                for(var i=0;i<click_times;i++)
                    $("th[data-id='"+state.sortKey+"']").find("a:first").trigger("click");
            }

            Backbone.on("backgrid:sort",function(colName,dir){                 
                var mode=self.options.data_mode || 'client'                
                if (dir==='ascending')
                    dir='asc';
                else if (dir==='descending')
                    dir='desc';
                else
                    dir='null';
                if( mode !=='server')
                {
                    $.post(self.options.personalize_url,
                            { 'do': 'grid.state', grid: self.options.id,s:colName,sd:dir },
                            function(response, status, xhr) {
                                console.log(response, status, xhr);
                            }
                        );
                }
                return true;
            });
            

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
})
