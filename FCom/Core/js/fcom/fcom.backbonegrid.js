define(['backbone', 'underscore', 'jquery', 'jquery.cookie', 'jquery.tablesorter','jquery.dragtable'], function(Backbone, _, $) {

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
        url : function() {
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

            this.$el.attr('class',this.model.get('cssClass') + ' sort-' + this.model.get('sortState'));

            return this;
        }
    });

    BackboneGrid.Views.HeaderView = Backbone.View.extend({
        render: function() {
            this.collection.each(this.addTh, this);
            return this;
        },
        addTh: function(thModel) {
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
             _.templateSettings.variable = "col";
        },
        render: function() {
            this.setElement($(this.template(this.model.toJSON())));
            return this;
        }
    });

    BackboneGrid.Views.GridView = Backbone.View.extend({
        //el: '.fcom-htmlgrid__grid tbody',
        initialize: function() {
            this.collection.on('reset', this.beforeRender, this);
        },
        beforeRender: function() {
            var models = this.collection.models;
            for(var i in models) {
                var cssClass = i % 2 == 0 ? 'even' : 'odd';
                models[i].set('cssClass', cssClass);
            }

            this.render();
        },
        render: function() {
            this.$el.html('');
            this.collection.each(this.addRow, this);

            this.$el.parents('table:first').dragtable({dragHandle:'.draghandle'});
            return this;
        },
        addRow: function(row) {
            var rowView = new BackboneGrid.Views.RowView({
                model: row
            });
            this.$el.append(rowView.render().el);
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

    FCom.BackboneGrid = function(config) {
        //Theader
        BackboneGrid.Models.ThModel.prototype.personalize_url = config.personalize_url;

        BackboneGrid.Views.ThView.prototype.template = _.template($('#'+config.headerTemplate).html());
        BackboneGrid.Views.HeaderView.prototype.el = "#" + config.id + " thead tr";
        //Tbody
        BackboneGrid.Views.GridView.prototype.el = "#" + config.id + " tbody";
        BackboneGrid.Views.RowView.prototype.template = _.template($('#'+config.rowTemplate).html());
        BackboneGrid.Collections.Rows.prototype.data_url = config.data_url;
        //pager
        //BackboneGrid.Views.PageView.prototype.template = _.template($('#'+config.pageTemplate).html());
        var state = config.data.state;


        state.p = parseInt(state.p);
        state.mp = parseInt(state.mp);
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

            if (i%2 === 0) {
                rows[i].cssClass = "even";
            } else {
                rows[i].cssClass = "odd";
            }
            var rowModel = new BackboneGrid.Models.Row(rows[i]);
            rowsCollection.add(rowModel);
        }

        var gridView = new BackboneGrid.Views.GridView({collection: rowsCollection});
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
