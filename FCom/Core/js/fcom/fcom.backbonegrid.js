define(['backbone', 'underscore', 'jquery', 'nestable'], function(Backbone, _, $) {

    var BackboneGrid = {
        Models: {},
        Collections: {},
        Views: {},
        currentState: {},
        colsInfo: {},
        personalize_url: '',
        id: ''
    }

    BackboneGrid.Models.ColModel = Backbone.Model.extend({
        defaults: {
            style: '',
            type: '',
            no_reorder: false,
            sortState: '',
            cssClass: '',
            hidden: false,
            label: '',
            href: '',
            cell: ''
        }
    });

    BackboneGrid.Collections.ColsCollection = Backbone.Collection.extend({
        model: BackboneGrid.Models.ColModel,
        append: 1,
        comparator: function(col) {
            return parseInt(col.get('position'));
        }    
    });

    BackboneGrid.Views.ThView = Backbone.View.extend({
        events: {
            'click a': 'changesortState'
        },
        initialize: function() {
            this.model.on('change', this.render, this);
            this.setElement($(this.template(this.model.toJSON())));

        },
        changesortState: function(ev) {

            var status = this.model.get('sortState');

            if (status === '')
                status = 'asc';
            else if (status === 'asc')
                status = 'desc';
            else
                status = '';
            this.model.set('sortState', status);

            columnsCollection.each(function(m) {
                if(m.get('sortState') !== '' && m.get('name')!== this.model.get('name')) {
                    m.set('sortState', '');
                }
            }, this);

            BackboneGrid.currentState.s = status !=='' ? this.model.get('name') : '';
            BackboneGrid.currentState.sd = this.model.get('sortState');

            rowsCollection.fetch({reset: true});

            //this.model.save();

            ev.preventDefault();
            return false;

        },
        render: function() {
            var accept = '';
            if (this.model.get('no_reorder') !== true)
                accept = 'accept ';
            this.$el.attr('class',accept + this.model.get('cssClass') + ' sort-' + this.model.get('sortState'));

            return this;
        }
    });

    BackboneGrid.Views.HeaderView = Backbone.View.extend({
        initialize: function() {
            this.collection.on('sort', this.render, this);
        },
        render: function() {
            this.$el.html('');            
            this.collection.each(this.addTh, this);


            gridParent = $('#'+BackboneGrid.id).parent();
            
            $('thead th', gridParent).resizable({
                handles: 'e',
                minWidth: 20,
                stop: function(ev, ui) {
                    var $el = ui.element, width = $el.width();                    
                    $('tbody td[data-col="'+$el.data('id')+'"]', gridParent).width(width);
                    $.post(columnsCollection.personalize_url,
                        { 'do': 'grid.col.width', grid: BackboneGrid.id, col: $el.data('id'), width: width },
                        function(response, status, xhr) {
                            //console.log(response, status, xhr);
                        }
                    );

                    colModel = columnsCollection.findWhere({name: $el.data('id')});
                    colModel.set('width', width);
                }
            });

            return this;
        },
        addTh: function(ColModel) {
            //console.log(ColModel.get('hidden'));
            if (!ColModel.get('hidden')) {
                var th = new BackboneGrid.Views.ThView({model: ColModel});
                this.$el.append(th.render().el);
            }

        }
    });
    BackboneGrid.Models.Row = Backbone.Model.extend({
        defaults: {
            _actions: ' ',            
            description: '',
            version: '',
            run_status: '',
            run_level: '',
            run_level_core: '',
            requires: '',
            required_by: '',
            colsInfo: []
        }
    });

    BackboneGrid.Collections.Rows = Backbone.Collection.extend({
        model: BackboneGrid.Models.Row,
        initialize: function() {
            this.updateColsInfo();
        },
        updateColsInfo: function() {
            colsInfo = [];
            columnsCollection.each(function(c) {
                var colInfo = {};                
                colInfo.href = c.get('href');
                colInfo.name = c.get('name');
                colInfo.hidden = c.get('hidden');
                colInfo.cell =c.get('cell');
                colInfo.position = c.get('position');   
                //colInfo.width = c.get('width');             
                colsInfo[colsInfo.length] = colInfo;
            },this);
            
            this.each(function(row) {
                row.set('colsInfo', colsInfo);
            },this);
        },
        url: function() {
            var append = '';
            _.each(BackboneGrid.currentState, function(v, k, l) {
                if( append != '')
                    append += '&';
                append += (k + '=' + v);
            });

            return this.data_url + '?' + append;
        },
        parse: function(response) {
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
        initialize: function() {                 
             
             this.model.on('change',this.render,this);
        },
        

        render: function() {            
            this.setElement($(this.template(this.model.toJSON())));
            return this;
        }
    });

    BackboneGrid.Views.GridView = Backbone.View.extend({
        el: 'div.scrollable-area',
        setCss: function() {        
            //console.log('setcss');
            var models = this.collection.models;
            for(var i in models) {
                var cssClass = i % 2 == 0 ? 'even' : 'odd';
                models[i].set('cssClass', cssClass);
            }
        },        
        /*saveColumnSize: function(ev) {
            var table = $(ev.currentTarget);
            var cols = [];
            table.find('th').each(function () {
                var name = $(this).attr('data-id');
                if (columnsCollection.columnsWidth[name] && columnsCollection.columnsWidth[name]!=$(this).width()) {
                    columnsCollection.columnsWidth[name] = $(this).width();
                    cols[cols.length] = {name: name, width: $(this).width()};
                }
            });

            $.post(columnsCollection.personalize_url,{
                'do': 'grid.col.widths',
                'cols': cols,
                'grid': columnsCollection.grid                    
            });
        },
        resetColResizer: function() {
            this.getMainTable().colResizable({
                disable: true
            });
            this.getMainTable().colResizable({
                onResize: this.saveColumnSize
            });
        },*/
        getMainTable : function() {
            return $('#' + BackboneGrid.id);
        },
        render: function() {     
            //console.log('gridview-render');
            this.setCss();
            this.$el.html('');
            this.collection.each(this.addRow, this);
            
            return this;
        },
        addRow: function(row) {
            var rowView = new BackboneGrid.Views.RowView({
                model: row
            });
            this.$el.append(rowView.render().el);
        }
    });
    BackboneGrid.Views.ColCheckView = Backbone.View.extend({
        tagName: 'li',
        className: 'dd-item dd3-item',
        attributes: function () {
            return {'data-id': this.model.get('name')};
        },
        events: {
            'change input.showhide_column': 'changeState',
            'click input.showhide_column': 'preventDefault'
        },
        preventDefault: function(ev){

            ev.stopPropagation();
        },
        changeState: function(ev) {
           
            this.model.set('hidden',!this.model.get('hidden'));
            headerView.render();

            var name = 'hidden' + this.model.get('name');
            var value = this.model.get('hidden');
            gridView.collection.each(function(row) {
                row.set(name, value);
            });

            $.post(columnsCollection.personalize_url,{
                'do': 'grid.col.hidden',
                'col': this.model.get('name'),
                'hidden': value,
                'grid': columnsCollection.grid
            });
            rowsCollection.updateColsInfo();
            gridView.render();

            
        },
        render: function() {
            this.$el.html(this.template(this.model.toJSON()));
            return this;
        }
    });
    BackboneGrid.Views.ColsVisibiltyView = Backbone.View.extend({                        
        initialize: function() {
            this.setElement('#' + BackboneGrid.id + ' .dd-list');            
        },
        orderChanged: function(ev) {
            var orderJson = $('.dd').nestable('serialize');
            for(var i in orderJson) {
                var key = orderJson[i].id;
                colModel = columnsCollection.findWhere({name: key});
                colModel.set('position', parseInt(i) + columnsCollection.append);
            }
            
            columnsCollection.sort();            
            //console.log(columnsCollection.pluck('position'));
            //console.log(columnsCollection.pluck('label'));
            //console.log(columnsCollection.pluck('width'));
            
            rowsCollection.updateColsInfo();
            gridView.render();

             $.post(columnsCollection.personalize_url,{
                'do': 'grid.col.orders',
                'cols': colsInfo,
                'grid': columnsCollection.grid
            });
            
           
        },
        render: function() {            
            this.$el.html('');
            this.collection.each(this.addLiTag, this);
            
            // not working
            /*this.$el.find('.dd:first').nestable().on('change',function(){          
            });*/

            // working
            $('.dd').nestable().on('change',this.orderChanged);                            
        },
        addLiTag: function(model) {
            if(model.get('label') !== '') {
                var checkView = new BackboneGrid.Views.ColCheckView({model:model});
                this.$el.append(checkView.render().el);
            }
        }
    });
    
    function updatePageHtml(p,mp) {

            p = BackboneGrid.currentState.p;
            mp = BackboneGrid.currentState.mp;
            var html = '';

            html += '<li class="first'+ (p<=1 ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&laquo;</a>';
            html += '</li>';

            html += '<li class="prev'+ (p<=1 ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&lsaquo;</a>';
            html += '</li>';


            for (var i= Math.max(p-3,1); i<=Math.min(p+3,mp);i++) {
                html += '<li class="page' + (i == p ? ' active' : '') + '">';
                html += '<a class="js-change-url" data-page="" href="#">' +  i +'</a>';
                html += '</li>';
            }

            html += '<li class="next'+ (p>=mp ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&rsaquo;</a>';
            html += '</li>';

            html += '<li class="last'+ (p>=mp ? ' disabled' : '') + '">';
            html += '<a class="js-change-url" href="#">&raquo;</a>';
            html += '</li>';

            $('ul.pagination.page').html(html);
    }
    var rowsCollection;
    var columnsCollection;
    var gridView;
    var headerView;    
    var colsInfo;
    FCom.BackboneGrid = function(config) {

        _.templateSettings.variable = 'rc';
        //Theader
        BackboneGrid.Collections.ColsCollection.prototype.personalize_url = config.personalize_url;
        BackboneGrid.Collections.ColsCollection.prototype.grid = config.id;
        BackboneGrid.Models.ColModel.prototype.personalize_url = config.personalize_url;
        
        BackboneGrid.Views.ThView.prototype.template = _.template($('#'+config.headerTemplate).html());
        BackboneGrid.Views.HeaderView.prototype.el = "#" + config.id + " thead tr";
        //Tbody
        BackboneGrid.Views.GridView.prototype.el = "#" + config.id + " tbody";        
        BackboneGrid.Views.RowView.prototype.template = _.template($('#'+config.rowTemplate).html());
        BackboneGrid.Collections.Rows.prototype.data_url = config.data_url;

        //column visiblity checkbox view
        BackboneGrid.Views.ColCheckView.prototype.template = _.template($('#'+config.colTemplate).html());
        var state = config.data.state;


        state.p = parseInt(state.p);
        state.mp = parseInt(state.mp);
        BackboneGrid.id = config.id;
        BackboneGrid.currentState = state;

        $('ul.pagination.page').on('click', 'li', function(ev) {
            var li = $(this);
            if(li.hasClass('first'))
                BackboneGrid.currentState.p = 1;
            if(li.hasClass('next'))
                BackboneGrid.currentState.p ++;
            if(li.hasClass('prev'))
                BackboneGrid.currentState.p --;
            if(li.hasClass('last'))
                BackboneGrid.currentState.p = BackboneGrid.currentState.mp;
            if(li.hasClass('page'))
                BackboneGrid.currentState.p = parseInt(li.find('a').html());
            updatePageHtml();
            rowsCollection.fetch({reset: true});
            ev.preventDefault();
            return;
        });

        updatePageHtml();

        //header view
        var columns = config.columns;
        columnsCollection = new BackboneGrid.Collections.ColsCollection;
        var columnsWidth = {};
        for (var i in columns) {
            var c = columns[i];
            if (c.name != 'id') {

                if (c.hidden === 'false')
                    c.hidden = false;                
                if (c.name === 0) {                    
                    columnsCollection.append = 2;
                }

                c.id = config.id + '-' + c.name;
                //c.style = c['width'] ? "width:" + c['width'] + "px" : '';

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

                var ColModel = new BackboneGrid.Models.ColModel(c);
                columnsCollection.add(ColModel);
                if(c['width'])
                    columnsWidth[c.name+''] = c['width'];
                //console.log(c);
                //BackboneGrid.Models.Row.prototype.defaults['hidden' + c.name] = !(!c['hidden']);
            }
        }

        columnsCollection.columnsWidth = columnsWidth;

        headerView = new BackboneGrid.Views.HeaderView({collection: columnsCollection});
        headerView.render();
        var colsVisibiltyView = new BackboneGrid.Views.ColsVisibiltyView({collection: columnsCollection});
        colsVisibiltyView.render();
        //body view
        var rows = config.data.data;
        rowsCollection = new BackboneGrid.Collections.Rows;

        for (var i in rows) {            
            var rowModel = new BackboneGrid.Models.Row(rows[i]);            
            rowsCollection.add(rowModel);
        }
        rowsCollection.updateColsInfo();
        gridView = new BackboneGrid.Views.GridView({collection: rowsCollection});        
        gridView.render();

        $('ul.pagination.pagesize a').click(function(ev){
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
});
