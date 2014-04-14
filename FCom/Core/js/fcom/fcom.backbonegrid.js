/*
*Utility functions
*/
function validationRules(rules) {
    var str = '';
    for (var key in rules) {
        switch (key) {
            case 'required':
                str += 'data-rule-required="true" ';
                break;
            case 'email':
                str += 'data-rule-email="true" ';
                break;
            case 'number':
                str += 'data-rule-number="true" ';
                break;
            case 'digits':
                str += 'data-rule-digits="true" ';
                break;
            case 'ip':
                str += 'data-rule-ipv4="true" ';
                break;
            case 'url':
                str += 'data-rule-url="true" ';
                break;
            case 'phoneus':
                str += 'data-rule-phoneus="true" ';
                break;
            case 'minlength':
                str += 'data-rule-minlength="' + rules[key] + '" ';
                break;
            case 'maxlength':
                str += 'data-rule-maxlength="' + rules[key] + '" ';
                break;
            case 'max':
                str += 'data-rule-max="' + rules[key] + '" ';
                break;
            case 'min':
                str += 'data-rule-min="' + rules[key] + '" ';
                break;
            case 'range':
                str += 'data-rule-range="[' + rules[key][0] + ',' + rules[key][1] + ']" ';
                break;
            case 'date':
                str += 'data-rule-dateiso="true" data-mask="9999-99-99" placeholder="YYYY-MM-DD" ';
                break;
        }
    }

    return str;
}

function filesizeFormat(size) {
    var size = parseInt(size);
    if (size/(1024*1024) > 1) {
        size = size/(1024*1024);
        size = size.toFixed(2)+' MB';
    } else if (size/1024 > 1) {
        size = size/1024;
        size = size.toFixed(2)+' KB';
    } else {
        size = size+' Byte';
    }

    return size;
}

function dateTimeNow() {
    var d = new Date();
    var dateTime = d.getFullYear()+ '-' + toString((d.getMonth() + 1)) + '-' + toString(d.getDate())+ ' '+ toString(d.getHours())+ ':' + toString(d.getMinutes()) + ':' + toString(d.getSeconds());
    function toString(val) {
       return (val <  10) ? '0' + val: val;
    }
    return dateTime;
}

define(['backbone', 'underscore', 'jquery', 'ngprogress', 'select2',
    'jquery.quicksearch', 'unique', 'jquery.validate', 'datetimepicker', 'jquery-ui', 'moment', 'daterangepicker'],
    function (Backbone, _, $, NProgress) {

        FCom.BackboneGrid = function (config) {
            var rowsCollection;
            var filtersCollection;
            var columnsCollection;
            var filtersCollection;
            var gridView;
            var headerView;
            var filterView;
            var colsInfo;
            var selectedRows;
            var settings;
            var modalForm;
            var BackboneGrid = {
                Models: {},
                Collections: {},
                Views: {},
                currentState: {},
                colsInfo: {},
                data_mode: 'server',
                multiselect_filter: false,
                local_personalize: false

            }

            function validateUnique(element, model ,editInline) {
                var url = model.get('validation').unique;
                element.rules("add", {
                    onfocusout: false,
                    onkeyup: false,
                    remote: {
                    url: url,
                    type: 'post',
                    data: {
                            _name: model.get('name')
                    },
                    dataFilter: function (responseString) {
                        var response = jQuery.parseJSON(responseString);
                        currentMessage = response.Message;
                        if ((modalForm.modalType === 'editable' || editInline) && BackboneGrid.currentRow.get('id') === response.id){
                            return true;
                        }
                        return response.unique;
                    },
                    async:false
                    },
                    messages: {
                        remote: "This " + model.get('label') + " is already taken place"
                    }
                });
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
                    cell: '',
                    selectedCount: 0
                },
                initialize: function () {
                    if (this.type === 'multiselect') {
                        this.set('selectedCount', selectedRows.length);
                    }
                }
            });

            BackboneGrid.Collections.ColsCollection = Backbone.Collection.extend({
                model: BackboneGrid.Models.ColModel,
                append: 1,
                comparator: function (col) {
                    return parseInt(col.get('position'));
                },
                initialize: function () {
                    this.on('add', this._addDefault, this);
                    this.on('remove', this._removeDefault, this);
                },
                _addDefault: function (c) {
                    if (typeof(c.get('default')) !== 'undefined') {
                        BackboneGrid.Models.Row.prototype.defaults[c.get('name')] = c.get('default');
                    }
                },
                _removeDefault: function (c) {
                    if (typeof(BackboneGrid.Models.Row.prototype.defaults[c.get('name')]) !== 'undefined')
                        delete BackboneGrid.Models.Row.prototype.defaults[c.get('name')];
                }
            });

            BackboneGrid.Views.ThView = Backbone.View.extend({
                tagName: 'th',
                className: function () {
                    var cssClass = this.model.get('cssClass');
                    if (this.model.get('sortState').length > 0) {
                        cssClass += (' th-sorting-' + this.model.get('sortState'));
                    }
                    return cssClass;
                },
                attributes: function () {
                    var hash = {};
                    hash['data-id'] = this.model.get('name');
                    if (this.model.has('width')) {
                        hash['style'] = "width: " + this.model.get('width') + 'px;';
                    }
                    /*
                    if (this.model.has('overflow')) {
                        hash['style'] += 'overflow:hidden;'
                    }
                    */
                    return hash;
                },
                events: {
                    'click a.js-change-url': '_changesortState',
                    'click ul.dropdown-menu.js-sel>li>a': '_checkAction'
                },
                initialize: function () {
                    this.model.on('render', this.render, this);
                },
                _selectPageAction: function (flag) {
                    var temp = [];
                    rowsCollection.each(function (model) {
                        if (model.get('_selectable')) {
                            model.set('selected', flag);
                            temp.push(model.toJSON());
                        }
                    });

                    if (flag) {
                        selectedRows.reset(_.union(selectedRows.toJSON(), temp));
                    } else {
                        var ids = _.pluck(temp, 'id');
                        var newRows = [];
                        selectedRows.each(function (row) {
                            if (_.indexOf(ids, row.get('id')) === -1)
                                newRows.push(row.toJSON());
                        });
                        selectedRows.reset(newRows);
                    }

                    rowsCollection.each(function (model) {
                        if (model.get('_selectable')) {
                            model.set('selected', flag);
                        }
                    });

                    gridView.$el.find('input.select-row:not([disabled])').prop('checked', flag);
                },
                _checkAction: function (ev) {
                    if ($(ev.target).attr('href').indexOf('upd') !== -1)
                        this._selectAction(ev.target);
                    else
                        this._showAction(ev.target);
                },
                //function to show All,Selected or Unselelected rows
                _showAction: function (eleSelected) {
                    var key = $(eleSelected).attr('href').replace('#', '');
                    var displayType = $('.f-grid-display-type');
                    switch (key) {
                        case 'show_all':
                            if (BackboneGrid.showingSelected) {
                                displayType.find('span.icon-placeholder').html('<i class="glyphicon glyphicon-list"></i>');
                                displayType.find('span.title').html('A');
                                BackboneGrid.data_mode = BackboneGrid.prev_data_mode;
                                rowsCollection.reset(BackboneGrid.prev_originalRows);
                                BackboneGrid.showingSelected = false;
                                $('.f-grid-bottom.f-grid-toolbar.'+BackboneGrid.id+' > div.pagination').css('display', 'block');
                                /*if (BackboneGrid.data_mode !== 'local') {
                                    rowsCollection.fetch({reset: true});
                                } else {*/
                                    gridView.render();
                                //}
                            }
                            break;
                        case 'show_sel':
                            if (!BackboneGrid.showingSelected) {
                                displayType.find('span.icon-placeholder').html('<i class="glyphicon glyphicon-th-list"></i>');
                                displayType.find('span.title').html('S');
                                BackboneGrid.prev_data_mode = BackboneGrid.data_mode;

                                BackboneGrid.prev_originalRows = rowsCollection.toJSON();
                                BackboneGrid.showingSelected = true;
                                $('.f-grid-bottom.f-grid-toolbar.' + BackboneGrid.id + ' > div.pagination').css('display', 'none');

                                BackboneGrid.data_mode = 'local';

                                rowsCollection.reset(selectedRows.toJSON());

                            }
                            break;
                    }

                },
                _clearSelection: function () {
                    selectedRows.reset();
                    rowsCollection.each(function (model) {
                        if (model.get('selected'))
                            model.set('selected', false);
                        //model.trigger('render');
                    });
                    gridView.$el.find('input.select-row:not([disabled])').prop('checked', false);
                    $(BackboneGrid.MassDeleteButton).addClass('disabled');
                    $(BackboneGrid.MassEditButton).addClass('disabled');
                },
                //function to select or unselect all rows of page and empty selected rows
                _selectAction: function (eleSelected) {
                    var key = $(eleSelected).attr('href').replace('#', '');
                    switch (key) {
                        case 'upd_sel': //select all rows of a page
                            this._selectPageAction(true);
                            break;
                        case 'upd_unsel'://unselect all rows of a page
                            this._selectPageAction(false);
                            break;
                        case 'upd_clear': //empty selected rows collection
                            this._clearSelection();
                            break;
                    }

                    //this.model.set('selectedCount', selectedRows.length);
                },
                _changesortState: function (ev) {

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


                    if (BackboneGrid.data_mode === 'local') {
                        gridView.render();
//                        rowsCollection.sortLocalData();
                        rowsCollection.saveLocalState();
                    } else {
                        rowsCollection.fetch({reset: true});
                        //gridView.render();
                    }
                    this.$el.attr('class', this.className());
                    ev.preventDefault();
                    headerView.render();

                    return false;
                },
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));
                    this.$el.attr('class', this.className());

                    return this;
                }
            });

            BackboneGrid.Views.HeaderView = Backbone.View.extend({
                initialize: function () {
                    this.collection.on('sort', this.render, this);
                    this.collection.on('render', this.render, this);
                },
                render: function () {
                    this.$el.html('');
                    this.collection.each(this.addTh, this);
                    gridParent = $('#' + BackboneGrid.id).parent();
                    $('thead th', gridParent).resizable({
                        handles: 'e',
                        minWidth: 20,
                        stop: function (ev, ui) {
                            var $el = ui.element, width = $el.width();
                            $.post(BackboneGrid.personalize_url,
                                { 'do': 'grid.col.width', grid: BackboneGrid.id, col: $el.data('id'), width: width },
                                function (response, status, xhr) {

                                }
                            );
                            colModel = columnsCollection.findWhere({name: $el.data('id')});
                            colModel.set('width', width);
                            //$(ev.target).append('<div class="ui-resizable-handle ui-resizable-e" style="z-index: 90;"></div>');
                            return true;
                        }
                    });

                    return this;
                },
                addTh: function (ColModel) {
                    if (!ColModel.get('hidden')) {
                        var th = new BackboneGrid.Views.ThView({model: ColModel});
                        this.$el.append(th.render().el);
                    }
                }
            });
            BackboneGrid.Models.Row = Backbone.Model.extend({
                defaults: {
                    _actions: ' ',
                    selected: false,
                    editable: true,
                    _selectable: true
                },
                initialize: function () {
                    this.checkSelectVal();
                    //this.on('change', this.checkSelectVal, this);
                },
                checkSelectVal: function () {

                    var isSelected = typeof(selectedRows.findWhere({id: this.get('id')})) !== 'undefined';
                    this.set('selected', isSelected);
                },
                destroy: function () {
                    var id = this.get('id');

                    if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length > 0) {
                        var hash = {};
                        hash.id = id;
                        hash.oper = 'del';
                        $.post(BackboneGrid.edit_url, hash);
                    }

                    return false;
                },
                save: function (not_render) {

                    var self = this;
                    var id = this.get('id');
//                    var hash = this.changedAttributes(); //todo: check why sometimes cannot detect attributes is changed
                    var hash = this.attributes;
                    hash.id = id;
                    hash.oper = 'edit';

                    if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length > 0) {
                        if (this.get('_new')) {
                            hash.oper = 'add';
                            $.post(BackboneGrid.edit_url, hash, function (data) {
                                self.set('id', data.id);
                                self.set('_new', false);
                            });
                        } else {
                            $.post(BackboneGrid.edit_url, hash);
                        }

                    }
                    if (!not_render)
                        this.trigger('render');

                    $(BackboneGrid.quickInputId).quicksearch('table#' + BackboneGrid.id + ' tbody tr');
                }
            });


            BackboneGrid.Collections.Rows = Backbone.Collection.extend({
                model: BackboneGrid.Models.Row,
                initialize: function (models) {
                    if (BackboneGrid.data_mode === 'local') {
                        this.on('add remove', this.updatePageInfo, this);
                    }
                },
                _addRow: function (ev) {
                    if (ev.grid === BackboneGrid.id) {
                        var newRow = new BackboneGrid.Models.Row(ev.row);
                        rowsCollection.add(newRow);
                        gridView.render();
                    }
                },
                filterLocalData: function (data) {

                    var temp = this.clone();
                    for (var filter_key in BackboneGrid.current_filters) {
                        var filter_val = BackboneGrid.current_filters[filter_key].val;
                        var type = filtersCollection.findWhere({field: filter_key}).get('type');
                        if (filter_val == '') {
                            type = '';
                        }
                        switch (type) {
                            case 'text':
                                var filterVal = BackboneGrid.current_filters[filter_key].val + '';
                                var op = BackboneGrid.current_filters[filter_key].op;
                                var check = {};
                                switch (op) {
                                    case 'contains':
                                        check.contain = true;
                                        break;
                                    case 'start':
                                        check.contain = true;
                                        check.start = true;
                                        break;
                                    case 'end':
                                        check.contain = true;
                                        check.end = true;
                                        break;
                                    case 'equal':
                                        check.contain = true;
                                        check.end = true;
                                        check.start = true;
                                        break;
                                    case 'not':
                                        check.contain = false;
                                        break;
                                }
                                filterVal = filterVal.toLowerCase();
                                temp.models = _.filter(temp.models, function (model) {
                                    var flag = true;
                                    var modelVal = model.get(filter_key) + '';
                                    modelVal = modelVal.toLowerCase();
                                    var first_index = modelVal.indexOf(filterVal);
                                    var last_index = modelVal.lastIndexOf(filterVal);
                                    for (key in check) {
                                        switch (key) {
                                            case 'contain':
                                                flag = flag && ((first_index !== -1) === check.contain);
                                                break;
                                            case 'start':
                                                flag = flag && first_index === 0;
                                                break;
                                            case 'end':
                                                flag = flag && (last_index + filterVal.length) === modelVal.length;
                                                break;
                                        }

                                        if (!flag)
                                            return flag;
                                    }

                                    return flag;
                                }, this);

                                break;
                            case 'multiselect':
                                filter_val = filter_val.split(',');
                                temp.models = _.filter(temp.models, function (model) {

                                    var flag = false;
                                    for (var i in filter_val) {
                                        flag = flag || filter_val[i].toLowerCase() === model.get(filter_key).toLowerCase();
                                    }

                                    return flag;
                                }, this);
                                break;
                        }

                    }

                    //this.reset(temp.toJSON(), {silent: true});
                    if (typeof (data) !== 'undefined'  && data.reset_page == true ) {
                        //TODO: confirm save value filter into database.
                        BackboneGrid.currentState.p = 1;
                    }

                    temp.length = temp.models.length;
                    return temp;
                },
                updatePageInfo: function() {

                    var clone = this.filterLocalData();
                    BackboneGrid.currentState.mp = Math.ceil(clone.length / BackboneGrid.currentState.ps);
                    BackboneGrid.currentState.c = clone.length;
                    updatePageHtml();
                },
                sortLocalData: function () {
                    if (BackboneGrid.currentState.s !== '' && BackboneGrid.currentState.sd !== '') {
                        this.comparator = function (col) {
                            return col.get(BackboneGrid.currentState.s);
                        };
                        if (BackboneGrid.currentState.sd === 'desc') {
                            this.comparator = this.reverseSortBy(this.comparator);
                        }
                        this.sort();
                    } else {
                        this.comparator = function (col) {
                            return col.get('id');
                        };
                        this.sort();
                    }
                    return this;

                },

                saveLocalState: function() {
                    //only if local_personalize configuration flag is true, we can personalize
                    if (BackboneGrid.local_personalize) {
                        $.post(BackboneGrid.personalize_url,
                            {
                                    'do': 'grid.state',
                                    'grid': BackboneGrid.id,
                                    's': BackboneGrid.currentState.s,
                                    'sd': BackboneGrid.currentState.sd,
                                    'p': BackboneGrid.currentState.p,
                                    'ps': BackboneGrid.currentState.ps
                            }
                        );
                    }
                },
                url: function () {
                    if (BackboneGrid.data_mode !== 'server') {
                        return false;
                    }

                    var append = '';
                    var keys = ['p', 's', 'sd', 'ps'];
                    for (var i in keys) {
                        if (append != '')
                            append += '&';
                        append += (keys[i] + '=' + BackboneGrid.currentState[keys[i]]);
                    }
                    append += ('&filters=' + JSON.stringify(BackboneGrid.current_filters));
                    var c = this.data_url.indexOf('?') === -1 ? '?' : '&';
                    return this.data_url + c + append + '&gridId=' + BackboneGrid.id;
                },
                parse: function (response) {
                    if (typeof (response[0]) !== 'undefined' && typeof(response[0].c) !== 'undefined') {

                        //  if (response[0].c !== BackboneGrid.currentState.c) {
                        var mp = Math.ceil(response[0].c / BackboneGrid.currentState.ps);
                        BackboneGrid.currentState.mp = mp;
                        BackboneGrid.currentState.c = response[0].c;
                        if (BackboneGrid.data_mode !== 'local')
                            updatePageHtml();
                        // }

                    }

                    return response[1];
                },
                reverseSortBy: function (sortByFunction) {
                    return function (left, right) {
                        var l = sortByFunction(left);
                        var r = sortByFunction(right);

                        if (l === void 0) return -1;
                        if (r === void 0) return 1;

                        return l < r ? 1 : l > r ? -1 : 0;
                    };
                }
            });

            BackboneGrid.Views.RowView = Backbone.View.extend({
                tagName: 'tr',
                className: function () {
                    return this.model.get('cssClass');
                },
                attributes: function () {
                    return {id: this.model.get('id')};
                },
                events: {
                    'change input.select-row': '_selectRow',
                    'change .form-control': '_cellValChanged',
                    //'blur .form-control': '_validate',
                    'click button.btn-delete': '_deleteRow',
                    'click button.btn-edit': '_editModal',
                    'click button.btn-custom': '_callbackCustom',
                    'click button.btn-edit-inline': 'editInline',
                    'click button.btn-save-inline': 'saveInline'
                },
                initialize: function () {
                    this.model.on('render', this.render, this);
                    this.model.on('remove', this._destorySelf, this);

                    //this.model.on('change', this.render, this);
                },
                _editModal: function (ev) {
                    modalForm.modalType = 'editable';
                    BackboneGrid.currentRow = this.model;
                    modalForm.render();
                    $(BackboneGrid.modalShowBtnId).trigger('click');
                    return true;
                },
                _validate: function (ev) {

                    var val = $(ev.target).val();
                    var name = $(ev.target).attr('data-col');
                    var col = columnsCollection.findWhere({name: name});
                    if (typeof(col) != 'undefined') {
                        if (typeof(col.get('validation')) !== 'undefined') {
                            var validation = col.get('validation');
                            if (validation.number) {
                                if (isNaN(val))
                                    $(ev.target).addClass('unvalid');
                                else
                                    $(ev.target).removeClass('unvalid');

                                return  !isNaN(val);
                            }
                            if (validation.required) {

                                var status = (val === '' || typeof(val) === 'undefined');
                                if (status)
                                    $(ev.target).addClass('unvalid');
                                else
                                    $(ev.target).removeClass('unvalid');

                                return  !status;
                            }
                        }
                    }

                    return true;

                },
                _selectRow: function (ev) {
                    var checked = $(ev.target).is(':checked');
                    this.model.set('selected', checked);

                    if (checked) {
                        selectedRows.add(this.model);
                    } else {
                        selectedRows.remove(this.model, {silent: true});
                        selectedRows.trigger('remove');

                        if (BackboneGrid.showingSelected) {
                            rowsCollection.remove(this.model, {silent: true});
                            //gridView.render();
                        }
                    }
                    ev.stopPropagation();
                    ev.preventDefault();

                    return;

                },
                _cellValChanged: function (ev) {
                    var val = $(ev.target).val();
                    var name = $(ev.target).attr('data-col');

                    //@todo why change cell must be saved?
                    this.model.set(name, val);
//                    this.model.save(true);

                },
                _deleteRow: function (ev) {

                    var confirm;
                    if ($(ev.target).closest('button').hasClass('noconfirm'))
                        confirm = true;
                    else
                        confirm = window.confirm("Do you want to really delete?");

                    if (confirm) {
                        rowsCollection.remove(this.model/*, {silent: true}*/);
                        selectedRows.remove(this.model, {silent: true});
//                        this._destorySelf();
                    }
                },
                _destorySelf: function () {
                    this.undelegateEvents();
                    this.$el.removeData().unbind();
                    this.remove();
                    this.model.destroy();
                },
                render: function () {
                    var colsInfo = columnsCollection.toJSON();

                    var rowJSON = this.model.toJSON();

                    var strRowSelect = '';
                    if (!this.model.get('_selectable')) {
                        strRowSelect = 'DISABLED ';
                    } else if (this.model.get('selected')) {
                        strRowSelect += 'CHECKED';
                    }

                    rowJSON._rowSelect = strRowSelect;



                    this.$el.html(this.template({row: rowJSON, colsInfo: colsInfo}));

                    if (typeof(this.afterRender) === 'function') {
                        this.afterRender();
                    }
                    return this;
                },

                setValidation: function () {
                    var self = this;
                    columnsCollection.each(function (col) {
                        if (col.get('editor') === 'select' && col.get('editable') === 'inline') {

                            var name = col.get('name');
                            self.$el.find('td[data-col="' + name + '"] select').val(self.model.get(name));
                        }
                    });


                },
                editInline: function (ev) {
                    var row = this;
                    var edit = false;
                    BackboneGrid.currentRow = this.model;
                    if ($(ev.target).parents('form').attr('id') != 'validate-inline') {
                        $(ev.target).parents('table').wrap('<form id="validate-inline" action="#"></form>');
                    }
                    $(ev.target).parents('table').parent().validate();
                    $(ev.target).parents('tr').find('td').each(function () {
                        var self = this;
                        columnsCollection.each(function (model) {
                            if (model.get('name') == $(self).attr('data-col') && model.get('edit_inline')) {
                                edit = true;
                                switch (model.get('editor')) {
                                    case 'select':
                                        $(self).html('<select name="'+model.get('name')+'" id="'+model.get('name')+'" class="form-control" '+ validationRules(model.get('validation')) +'></select>');
                                        var options = model.get('options');
                                        for (var index in options) {
                                            var selected = (index.toLowerCase() == row.model.get(model.get('name')).toLowerCase()) ? 'selected': '';
                                            $(self).find('select').append('<option value="'+index+'"'+ selected+'>'+ options[index]+'</option>');
                                        }
                                        $(self).attr('data-edit', 'select');
                                        break;
                                    case 'textarea':
                                        $(self).html('<textarea name="'+model.get('name')+'" id="'+model.get('name')+'" class="form-control" '+ validationRules(model.get('validation')) +'>' +row.model.get(model.get('name'))+'</textarea>');
                                        $(self).attr('data-edit', 'textarea');
                                        break;
                                    case 'text':
                                        $(self).attr('data-edit', 'input');
                                        $(self).html('<input name="'+model.get('name')+'" id="'+model.get('name')+'" class="form-control" type="text" value="'+ row.model.get(model.get('name')) +'" '+ validationRules(model.get('validation')) +'>');
                                        break;
                                    default:
                                        break;
                                }
                                if (typeof(model.get('validation')) !== 'undefined' && model.get('validation').unique) {
                                    validateUnique($(self).children(), model, true);
                                }
                            }
                        })
                    })
                    if (edit) {
                        $(ev.target).parents('tr').find('button.btn-save-inline').removeClass('hide');
                        $(ev.target).parent().addClass('hide');

                        if (BackboneGrid.callbacks && typeof(BackboneGrid.callbacks['before_edit_inline']) !== 'undefined') {
                            var func = BackboneGrid.callbacks['before_edit_inline'];
                            var script = func + '(this.$el,this.model.toJSON());';
                            eval(script);
                        }
                    }

                },
                saveInline: function (ev) {
                    var valid = true;
                    var self = this;
                    var previousAttributes = self.model.previousAttributes();
                    $(ev.target).parents('tr').find('input, select, textarea').each(function () {
                       if (!$(this).valid()) {
                           valid = false;
                       }
                    })
                    if (valid) {
                        $(ev.target).parents('tr').find('td').each(function (index) {
                            if ($(this).attr('data-edit') == 'select') {
                                var tmp = $(this).children().find('option[value="'+ $(this).children().val()+'"]');
                                $(this).html(tmp.html());
                                self.model.set($(this).attr('data-col'), tmp.val());
                            }
                            if ($(this).attr('data-edit') == 'input' || $(this).attr('data-edit') == 'textarea') {
                                self.model.set($(this).attr('data-col'), $(this).children().val());
                                $(this).html($(this).children().val());
                            }
                            if ($(this).attr('data-edit') == 'input[range]') {
                                self.model.set($(this).attr('data-col'), $(this).children().val());
                            }
                        })
                        var id = self.model.get('id');
                        var hash = self.model.attributes;
                        hash.id = id;
                        hash.oper = 'edit';
                        $.post(BackboneGrid.edit_url, hash,function (data) {
                            data.selected = false;
                            self.model.attributes = data;
                            self.render();
                        }).fail(function(data) {
                                self.model.attributes = previousAttributes;
                                self.render();
                                $.bootstrapGrowl('Error: cannot saved', { type: 'danger', align: 'center', width: 'auto' });
                            });
                        $(ev.target).parents('tr').find('button.btn-edit-inline').removeClass('hide');
                        $(ev.target).parent().addClass('hide');
                    }
                }
            });

            BackboneGrid.Views.GridView = Backbone.View.extend({
                //  el: 'table tbody',
                initialize: function () {
                    this.collection.on('reset', this.render, this);
                    this.collection.on('render', this.render, this);
                    this.collection.on('add', this.addRow, this);
                },
                setCss: function () {
                    var models = this.collection.models;
                    for (var i in models) {
                        var cssClass = i % 2 == 0 ? 'even' : 'odd';
                        models[i].set('cssClass', cssClass);
                    }
                },
                getMainTable: function () {
                    return $('#' + BackboneGrid.id);
                },
                render: function (data) {
                    this.setCss();
                    this.$el.html('');
                    if (config.data_mode == 'local') {
                        rowsCollection.sortLocalData();
                        var models = this.paginationLocalData();
                        _.each(models, function(model){
                            this.addRow(model);
                        }, this);
                    } else {
                        this.collection.each(this.addRow, this);
                    }

                    $(BackboneGrid.quickInputId).quicksearch('table#' + BackboneGrid.id + ' tbody tr');

                    return this;
                },
                addRow: function (row) {
                    var rowView = new BackboneGrid.Views.RowView({
                        model: row
                    });
                    this.$el.append(rowView.render().el);
                    rowView.setValidation();
                },
                clearSelectedRows: function() {
                    selectedRows.reset();
                    rowsCollection.each(function (model) {
                        if (model.get('selected'))
                            model.set('selected', false);
                        //model.trigger('render');
                    });
                    gridView.$el.find('input.select-row:not([disabled])').prop('checked', false);
                    $(BackboneGrid.MassDeleteButton).addClass('disabled');
                    $(BackboneGrid.MassEditButton).addClass('disabled');
                },
                paginationLocalData: function () {
                    var clone = this.collection.filterLocalData();
                    var models = [];
                    var page = (BackboneGrid.currentState.p - 1)*BackboneGrid.currentState.ps;
                    var len = Math.min(BackboneGrid.currentState.ps + page, clone.length);
                    for (var i=page;i<len;i++) {
                        models.push(clone.at(i));
                    }
                    //this.collection.reset(models, {silent: true});
                    BackboneGrid.currentState.mp = Math.ceil(clone.length / BackboneGrid.currentState.ps);
                    BackboneGrid.currentState.c = clone.length;
                    updatePageHtml();

                    return models;
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
                    'click input.showhide_column': 'preventDefault',
                    'click .dd3-content': 'preventDefault'
                },
                preventDefault: function (ev) {

                    ev.stopPropagation();
                },
                changeState: function (ev) {

                    this.model.set('hidden', !this.model.get('hidden'));
                    headerView.render();
                    filterView.render();

                    var name = 'hidden' + this.model.get('name');
                    var value = this.model.get('hidden');
                    gridView.collection.each(function (row) {
                        row.set(name, value, {silent: true});
                    });

                    $.post(BackboneGrid.personalize_url, {
                        'do': 'grid.col.hidden',
                        'col': this.model.get('name'),
                        'hidden': value,
                        'grid': columnsCollection.grid
                    });
                    gridView.render();

                    ev.stopPropagation();
                    return false;
                },
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));
                    return this;
                }
            });
            BackboneGrid.Views.ColsVisibilityView = Backbone.View.extend({
                initialize: function () {
                    this.setElement('.' + BackboneGrid.id + '.dd-list.columns');
                    this.collection.on('render', this.render, this);
                },
                orderChanged: function (ev) {
                    var postData = [];
                    this.$el.find('li').each(function (index) {
                        var c = columnsCollection.findWhere({name: $(this).data('id')});
                        c.set('position', index+1);
                        postData.push({name:c.get('name'), position:c.get('position'), hidden:c.get('hidden')});
                    });

                    columnsCollection.sort();
                    gridView.render();

                    $.post(BackboneGrid.personalize_url, {
                        'do': 'grid.col.orders',
                        'cols': postData,//columnsCollection.toJSON(),
                        'grid': columnsCollection.grid
                    });

                },
                render: function () {
                    this.$el.html('');
                    this.collection.each(this.addLiTag, this);

                    // not working
                    /*this.$el.find('.dd:first').nestable().on('change',function(){
                    });*/

                    // working
                    //$('.'+BackboneGrid.id+'.columns-span').nestable().on('change',this.orderChanged);
                },
                addLiTag: function (model) {
                    if (model.get('label') !== '' && !model.get('form_only')) {
                        var checkView = new BackboneGrid.Views.ColCheckView({model: model});
                        this.$el.append(checkView.render().el);
                    }
                }
            });
            BackboneGrid.Views.FilterCheckView = Backbone.View.extend({
                tagName: 'li',
                className: 'dd-item dd3-item',
                attributes: function () {
                    return {'data-id': this.model.get('field')};
                },
                events: {
                    'change input.showhide_column': 'changeState',
                    'click input.showhide_column': 'preventDefault',
                    'click .dd3-content': 'preventDefault'
                },
                preventDefault: function (ev) {

                    ev.stopPropagation();
                },
                changeState: function (ev) {

                    this.model.set('hidden', !this.model.get('hidden'));
                    filterView.render();

                    $.post(BackboneGrid.personalize_url, {
                        'do': 'grid.filter.hidden',
                        'col': this.model.get('field'),
                        'hidden': this.model.get('hidden'),
                        'grid': BackboneGrid.id
                    });

                    ev.stopPropagation();
                    return false;
                },
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));

                    return this;
                }
            });
            BackboneGrid.Views.FiltersVisibilityView = Backbone.View.extend({
                initialize: function () {
                    this.setElement('.' + BackboneGrid.id + '.dd-list.filters');
                    this.collection.on('render', this.render, this);
                },
                orderChanged: function (ev) {

                    this.$el.find('li').each(function (index) {
                        var filter = filtersCollection.findWhere({field: $(this).data('id')});
                        filter.set('position', index);
                    });

                    filtersCollection.sort();
                    filterView.render();


                    $.post(BackboneGrid.personalize_url, {
                        'do': 'grid.filter.orders',
                        'cols': filtersCollection.toJSON(),
                        'grid': BackboneGrid.id
                    });


                },
                render: function () {
                    this.$el.html('');
                    this.collection.each(this.addLiTag, this);
                },
                addLiTag: function (model) {
                    if (model.get('label') !== '') {
                        var checkView = new BackboneGrid.Views.FilterCheckView({model: model});
                        this.$el.append(checkView.render().el);
                    }
                }
            });
            BackboneGrid.Models.FilterModel = Backbone.Model.extend({
                defaults: {
                    hidden: false,
                    val: '',
                    position: 0,
                    range: true
                },
                initialize: function () {
                    var val = this.get('hidden');
                    if (val !== true && val !== false)
                        val = val === 'true' ? true : false;
                    this.set('hidden', val);
                }
            });
            BackboneGrid.Collections.FilterCollection = Backbone.Collection.extend({
                model: BackboneGrid.Models.FilterModel,
                append: 1,
                comparator: function (col) {
                    return parseInt(col.get('position'));
                }
            });
            BackboneGrid.Views.FilterCell = Backbone.View.extend({
                className: 'btn-group dropdown f-grid-filter',

                _filter: function (val) {
                    BackboneGrid.currentState.p = 1; //reset paging
                    if (val === false || val === '') {
                        this.$el.removeClass('f-grid-filter-val');
                        this.model.set('val', '');
                        if (typeof(BackboneGrid.current_filters[this.model.get('field')]) === 'undefined')
                            return;
                        this.render();

                    } else {
                        this.$el.addClass('f-grid-filter-val');

                        if (typeof(this.updateMainText) !== 'undefined')
                            this.updateMainText();
                    }

                    var filterJSON = this.model.toJSON();
//                    delete filterJSON['field'];
                    BackboneGrid.current_filters[this.model.get('field')] = filterJSON;

                    if (BackboneGrid.data_mode === 'local') {
                        gridView.render();
                        if (BackboneGrid.local_personalize) {
                            $.post(BackboneGrid.personalize_url,
                                {
                                        'do': 'grid.local.filters',
                                        'grid': BackboneGrid.id,
                                        'filters': JSON.stringify(BackboneGrid.current_filters)
                                }
                            );
                        }
                    } else {
                        rowsCollection.fetch({reset: true});
                    }


                },
                preventDefault: function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();

                    return false;
                },
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));
                    if (this.model.get('val') !== '') {
                        this.$el.addClass('f-grid-filter-val');
                    }
                    this.$el.append('<abbr class="select2-search-choice-close"></abbr>');

                    var self = this;
                    this.$el.find('abbr').click(function (ev) {
                        self._filter(false);
                    });

                    return this;
                },
                updateMainText: function () {
                    var html = '';
                    if (typeof(this.model.get('filterLabel')) !== 'undefined') {
                        html = this.model.get('filterLabel') + ' ';
                        html += ('"' + this.model.get('val') + '"');
                        html = html.charAt(0).toUpperCase() + html.slice(1);
                    } else {
                        html = this.model.get('val');
                        if (typeof(this.model.get('options')) !== 'undefined') {
                            var val = html.split(',');
                            var str = '';
                            for (var i in val) {
                                var tmp = this.model.get('options');
                                str += tmp[val[i]] + ',';
                            }
                            html = str.substring(0, str.length - 1);
                        }
                    }
                    this.$el.find('span.f-grid-filter-value').html($('<div/>').text(html).html());
                }
            });
            BackboneGrid.Views.FilterTextCell = BackboneGrid.Views.FilterCell.extend({
                events: {
                    'click input': 'preventDefault',
                    'click button.update': 'filter',
                    'click .filter-text-sub': 'subButtonClicked',
                    'click a.filter_op': 'filterOperatorSelected',
                    'keyup input': '_checkEnter'
                },
                initialize: function () {
                    this.model.set('op', 'contains');
                },
                _checkEnter: function (ev) {
                    var evt = ev || window.event;
                    var charCode = evt.keyCode || evt.which;
                    if (charCode === 13) {
                        this.$el.find('button.update').trigger('click');
                    }
                },
                filter: function () {
                    var val = this.$el.find('input:first').val();
                    this.model.set('val', val);
                    this._filter(val);
                },
                filterOperatorSelected: function (ev) {
                    //this.filterValChanged();
                    var operator = $(ev.target);
                    var text = operator.html();
                    this.model.set('op', operator.attr('data-id'));
                    this.model.set('filterLabel', text);
                    this.$el.find('button.filter-text-sub').html(text.charAt(0).toUpperCase() + text.slice(1) + "<span class='caret'></span>");
                    this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

                    return false;
                },
                subButtonClicked: function (ev) {
                    this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

                    return false;
                }
            });

            BackboneGrid.Views.FilterDateRangeCell = BackboneGrid.Views.FilterCell.extend({
                events: {
                    'click input': 'preventDefault',
                    'click button.update': 'filter',
                    //'click .filter-box': 'preventDefault',
                    'click .filter-text-sub': 'subButtonClicked',
                    'click a.filter_op': 'filterOperatorSelected'
                },
                initialize: function () {
                    //this.model.set('range', true);
                    this.model.set('op', 'between');
                },
                filterOperatorSelected: function (ev) {
                    this.model.set('range', $(ev.target).hasClass('range'));
                    if (this.model.get('range')) {
                        this.$el.find('div.range').css('display', 'table');
                        this.$el.find('div.not_range').css('display', 'none');
                    } else {
                        this.$el.find('div.range').css('display', 'none');
                        this.$el.find('div.not_range').css('display', 'table');
                    }

                    var operator = $(ev.target);
                    var text = operator.html();
                    this.model.set('op', operator.attr('data-id'));
                    this.model.set('filterLabel', operator.html());
                    text = text.charAt(0).toUpperCase() + text.slice(1);
                    this.$el.find('button.filter-text-sub').html(text + "<span class='caret'></span>");
                    this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

                    return false;
                },
                subButtonClicked: function (ev) {
                    this.$el.find('button.filter-text-sub').parents('div.dropdown:first').toggleClass('open');

                    return false;
                },
                filter: function () {

                    var filterVal;
                    if (this.model.get('range'))
                        filterVal = this.$el.find('input:first').val();
                    else
                        filterVal = this.$el.find('input:last').val();
                    this.model.set('val', filterVal);
                    this._filter(filterVal);

                },
                render: function () {
                    BackboneGrid.Views.FilterCell.prototype.render.call(this);
                    var self = this.$el;
                    var model = this.model;
                    this.$el.find('#daterange2').daterangepicker({
                        format: "YYYY-MM-DD"
                    }, function (start, end) {
                        return $('#date-range-text-' + model.get('field')).val(start.format("YYYY-MM-DD") + "~" + end.format("YYYY-MM-DD"));
                    });
                    this.$el.find(".datepicker").datetimepicker({
                        pickTime: false
                    });

                    $('.daterangepicker').on('click', function (ev) {
                            ev.stopPropagation();
                            ev.preventDefault();

                            return false;
                        }
                    );

                    var filterVal = this.model.get('val');
                    if (this.model.get('range'))
                        filterVal = this.$el.find('input:first').val(filterVal);
                    else
                        filterVal = this.$el.find('input:last').val(filterVal);

                    return this;
                }
            });
            BackboneGrid.Views.FilterNumberRangeCell = BackboneGrid.Views.FilterDateRangeCell.extend({
                render: function () {
                    BackboneGrid.Views.FilterCell.prototype.render.call(this);

                    return this;
                },
                filter: function () {
                    var filterVal;
                    if (this.model.get('range'))
                        filterVal = this.$el.find('input.js-number1').val() + '~' + this.$el.find('input.js-number2').val();
                    else
                        filterVal = this.$el.find('input.js-number').val();

                    this.model.set('val', filterVal);
                    this._filter(filterVal);
                }
            });
            BackboneGrid.Views.FilterMultiselectCell = BackboneGrid.Views.FilterCell.extend({
                events: {
                    'click button.update': 'filter',
                    'focusin div.select2-container': '_preventClose'
                },
                _preventClose: function () {
                    this.$el.addClass('js-prevent-close');
                    this.$el.find('ul.filter-box').css('display', 'block');
                },
                filter: function (val) {
                    this.$el.removeClass('js-prevent-close');
                    this.$el.find('ul.filter-box').css('display', '');
                    val = this.$el.find('#multi_hidden:first').val();
                    this.model.set('val', val);
                    this._filter(val);
                    /*var html = this.model.get('label')+': '+val;
                    html += '<span class="caret"></span>';
                    this.$el.find('button.filter-text-main').html(html);*/
                },
                render: function () {
                    BackboneGrid.Views.FilterCell.prototype.render.call(this);

                    var options = this.model.get('options');
                    var data = [];
                    for (var key in options) {
                        data[data.length] = {id: key, text: options[key]};
                    }

                    this.$el.find('#multi_hidden:first').select2({
                        multiple: true,
                        data: data,
                        placeholder: 'All'
                        //closeOnSelect: true
                    });

                    var self = this;
                    this.$el.find('#multi_hidden:first').change(function (ev) {
                        self._preventClose();
                    })
                    return this;
                }

            });

            BackboneGrid.Views.FilterSelectCell = Backbone.View.extend({
                className: 'btn-group dropdown f-grid-filter',
                _changeCss: function () {
                    this.$el.find('div.select2-container').addClass('btn-group');
                },
                filter: function (val) {
                    this.model.set('val', val);
                    BackboneGrid.Views.FilterCell.prototype._filter.call(this, val);
                },
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));
                    var fieldLabel = this.model.get('label');
                    var options = this.model.get('options');
                    var data = [];
                    for (var key in options) {
                        data[data.length] = {id: key, text: options[key]};
                    }
                    this.$el.find('#select_hidden:first').select2({
                        //multiple: this.model.get('filter_type') === 'select' ? false : true,
                        data: data,
                        placeholder: fieldLabel + ': All',
                        allowClear: true
                        //closeOnSelect: true
                    });

                    var self = this;
                    this.$el.find('#select_hidden:first').on('change', function () {
                        var val = $(this).val();
                        var temp = self.$el.find('div.select2-container span.select2-chosen');
                        if (val !== '') {
                            temp.html('<span class="f-grid-filter-field">' + fieldLabel + '</span>: <span class="f-grid-filter-value">' + options[val] + '</span>');
                        } else {
                            temp.html('<span class="f-grid-filter-field">' + fieldLabel + '</span>: <span class="f-grid-filter-value">All</span>');
                        }

                        self.filter(val);
                    });

                    this._changeCss();

                    var temp = this.$el.find('div.select2-container span.select2-chosen');
                    if (this.model.get('val') !== '') {
                        temp.html('<span class="f-grid-filter-field">' + fieldLabel + '</span>: <span class="f-grid-filter-value">' + options[this.model.get('val')] + '</span>');
                    }

                    return this;
                }

            });

            BackboneGrid.Views.FilterView = Backbone.View.extend({
                initialize: function () {
                    var div = 'span.' + BackboneGrid.id + '.f-filter-btns';
                    this.setElement(div);
                    this.collection.on('sort', this.render, this);
                },
                render: function () {
                    this.$el.html('');
                    this.collection.each(this.addFilterCol, this);
                },
                addFilterCol: function (model) {
                    if (model.get('hidden') === false) {
                        var filterCell;
                        switch (model.get('type')) {
                            case 'text':
                                filterCell = new BackboneGrid.Views.FilterTextCell({model: model});
                                break;
                            case 'date-range':
                                filterCell = new BackboneGrid.Views.FilterDateRangeCell({model: model});
                                break;
                            case 'number-range':
                                filterCell = new BackboneGrid.Views.FilterNumberRangeCell({model: model});
                                break;
                            case 'multiselect':
                                BackboneGrid.multiselect_filter = true;
                                filterCell = new BackboneGrid.Views.FilterMultiselectCell({model: model});
                                break;
                            case 'select':
                                filterCell = new BackboneGrid.Views.FilterSelectCell({model: model});
                                break;
                        }
                        this.$el.append(filterCell.render().el);
                    }
                }
            });

            BackboneGrid.Views.ModalElement = Backbone.View.extend({
                className: 'form-group',
                initialize: function() {
                    if (typeof(this.model.get('element_print')) !== 'undefined') {
                        this.model.set('editor', 'none');
                    }

                    this.model.set('_validation', validationRules(this.model.get('validation')));

                },
                render: function () {
                    this.$el.html(this.template(this.model.toJSON()));
                    return this;
                }
            });
            BackboneGrid.Views.ModalForm = Backbone.View.extend({
                initialize: function () {
                    this.modalType = 'mass-editable';
                    this.$el.parents('div.modal-dialog:first').find('button.save').click(this.saveChanges);
                },
                saveChanges: function (ev) {

                    modalForm.$el.find('textarea, input, select').each(function () {
                        var key = $(this).attr('id');
                        var val = $(this).val();
                        var flag = true;
                        if (modalForm.modalType === 'mass-editable') {
                            if ($(this).hasClass('ignore-validate')) {
                                flag = false;
                            }
                        }
                        if (flag) {
                            BackboneGrid.modalElementVals[key] = val;
                        }
                    });
                    modalForm.formEl.validate();
                    if (!modalForm.formEl.valid()) {
                        BackboneGrid.modalElementVals = {};
                        return false;
                    }

                    /*for (var key in BackboneGrid.modalElementVals) {
                        if (BackboneGrid.modalElementVals[key] === '' || BackboneGrid.modalElementVals[key] === null) {
                            delete BackboneGrid.modalElementVals[key];
                        }

                    }*/
                    if (modalForm.modalType === 'mass-editable') {

                        var ids = selectedRows.pluck('id').join(",");

                        if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length > 0 && !$.isEmptyObject(BackboneGrid.modalElementVals)) {
                            var hash = BackboneGrid.modalElementVals;
                            hash.id = ids;
                            hash.oper = 'mass-edit';
                            if (BackboneGrid.data_mode != 'local') {
                                $.post(BackboneGrid.edit_url, hash)
                                    .done(function (data) {
                                        if (data.success) {
                                            $.bootstrapGrowl("Successfully saved.", { type: 'success', align: 'center', width: 'auto' });
                                            selectedRows.each(function(model) {
                                                model.trigger('render');
                                            });
                                        } else {
                                            $.bootstrapGrowl(data.error, { type: 'danger', align: 'center', width: 'auto' });
                                        }

                                    });
                            }
                            delete BackboneGrid.modalElementVals.id;
                            delete BackboneGrid.modalElementVals.oper;
                        }

                        selectedRows.each(function (model) {
                            for (var key in BackboneGrid.modalElementVals) {
                                rowsCollection.each(function (row) {
                                    if (row.get('id') == model.get('id')) {
                                        row.set(key, BackboneGrid.modalElementVals[key], {silent: true});
                                    }
                                })
                            }
                        });
                        if (BackboneGrid.data_mode === 'local')
                            rowsCollection.trigger('mass_changed');
                    }

                    if (modalForm.modalType === 'addable') {
                        var hash = BackboneGrid.modalElementVals;
                        if (typeof(BackboneGrid.edit_url) !== 'undefined' && BackboneGrid.edit_url.length > 0) {
                            hash.oper = 'add';
                            $.post(BackboneGrid.edit_url, hash, function (data) {
                                var newRow = new BackboneGrid.Models.Row(data);
                                rowsCollection.add(newRow);
                                //gridView.addRow(newRow);
                            });
                        } else {
                            hash.id = guid();
                            var newRow = new BackboneGrid.Models.Row(hash);
                            rowsCollection.add(newRow);
                            //gridView.addRow(newRow);
                        }
                    }

                    if (modalForm.modalType === 'editable') {
                        for (key in BackboneGrid.modalElementVals) {
                            BackboneGrid.currentRow.set(key, BackboneGrid.modalElementVals[key], {silent: true});
                        }
                        BackboneGrid.currentRow.save();
                        rowsCollection.trigger('row_changed', BackboneGrid.currentRow);
                    }

                    //check this form is rendered in default view of grid or not.
                    if (ev && $(ev.target).prev().hasClass('f-grid-modal-close'))
                        $(ev.target).prev().trigger('click');

                    BackboneGrid.modalElementVals = {};

                    return true;

                },
                render: function () {
                    this.$el.html('');

                    var header;
                    switch (this.modalType) {
                        case 'addable':
                            header = 'Create Form';
                            BackboneGrid.currentRow = false;
                            break;
                        case 'mass-editable':
                            BackboneGrid.currentRow = false;
                            header = 'Mass Edit Form';
                            break;
                        case 'editable':
                            header = 'Edit Form';
                            break;
                    }
                    $(BackboneGrid.modalFormId).find('h4').html(header);
                    BackboneGrid.modalElementVals = {};
                    if (this.modalType === 'mass-editable') {
                        var self = this;
                        this.collection.each(function (model) {
                            if (model.has(self.modalType) && model.get(self.modalType)) {
                                var elementView = new BackboneGrid.Views.ModalMassGridElement({model: model});
                                $(BackboneGrid.Views.ModalForm.prototype.el).append(elementView.render().el);
                            }

                        })
                    } else {
                        this.collection.each(this.addElementDiv, this);
                    }
                    /*if (this.modalType === 'addable' || this.modalType ==='mass-editable')
                        $(BackboneGrid.modalFormId).find('select').val('');*/
                    if (this.modalType === 'mass-editable')
                        $(BackboneGrid.modalFormId).find('select').val('');

                    if (this.$el.is("form")) {
                        this.formEl = this.$el;
                    } else {
                        this.formEl = this.$el.parents('form:first');
                    }

                    if (this.modalType === 'mass-editable') {
                        this.formEl.validate({ignore: '.ignore-validate'});
                    } else {
                        this.formEl.validate({});
                    }
                    if (BackboneGrid.callbacks && typeof(BackboneGrid.callbacks['after_modalForm_render']) !== 'undefined') {
                        var func = BackboneGrid.callbacks['after_modalForm_render'];
                        var script = func + '(this.$el, rowsCollection.toJSON(), BackboneGrid.currentRow);';
                        eval(script);
                    }
                    this.collection.each(function (col) {
                        if (typeof(col.get('validation')) !== 'undefined' && typeof(col.get('validation').unique) !== 'undefined') {
                            if (modalForm.$el.find('#' + col.get('name')).length) {
                                validateUnique(modalForm.$el.find('#' + col.get('name')), col, false);
                            }
                        }
                    });

                    /*//focus to first input when modal shown
                    $(BackboneGrid.modalFormId).on('shown.bs.modal', function() {
                        $('input:text:visible:first', this).focus();
                    });*/

                    return this;

                },
                addElementDiv: function (model) {
                    if (model.has(this.modalType) && model.get(this.modalType)) {
                        var elementView = new BackboneGrid.Views.ModalElement({model: model});
                        this.$el.append(elementView.render().el);
                        if (this.modalType == 'addable') {
                            var country = this.$el.find('select#country');
                            if (country) {
                                country.val('US').prop('selected', true);
                            }
                        }

                        if (BackboneGrid.currentRow) {
                            var name = model.get('name');
                            var val = (typeof(BackboneGrid.currentRow.get(name)) !== 'undefined' ? BackboneGrid.currentRow.get(name) : '');
                            elementView.$el.find('#' + name).val(val);
                        }
                    }
                }
            });
            BackboneGrid.Views.ModalMassGridElement = Backbone.View.extend({
                className: 'form-group',
                render: function () {
                      this.$el.html(this.template(this.model.toJSON()));
                      return this;
                },
                events: {
                    'change .edit-field': 'selectFieldEdit'
                },
                selectFieldEdit: function (ev) {
                    if ($(ev.target).is(':checked')) {
                        modalForm.$el.find('#' + this.model.get('name')).removeClass('ignore-validate');
                    } else {
                        modalForm.$el.find('#' + this.model.get('name')).addClass('ignore-validate');
                    }
                }
            });
            $('body').on('click', 'button.remove-field', function () {
                $(BackboneGrid.modalFormId).find('select#'+ config.id + '-sel_sets').find('option[value="'+ $(this).attr('data-content')+'"]').removeClass('hide');
                $(this).parent().remove();

            })
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
                html += '<a class="js-change-url" href="#">' + mp + ' &raquo;</a>';
                html += '</li>';

                $('.' + BackboneGrid.id + '.pagination.page').html(html);

                //update page size options
                var pageSizeOpts = BackboneGrid.pageSizeOptions;

                var pageSizeOptsRender = [];
                for (var j = 0; j < pageSizeOpts.length; j++) {
                    var value = pageSizeOpts[j];
                    pageSizeOptsRender.push(value);
                    if (BackboneGrid.currentState.c <= value) {
                        if (BackboneGrid.currentState.ps > value) { //fix current page size
                            BackboneGrid.currentState.ps = _.last(pageSizeOptsRender);
                        }
                        break;
                    }
                }
                //render page size options html
                var pageSizeHtml = '';
                for (j = 0; j < pageSizeOptsRender.length; j++) {
                    pageSizeHtml += '<li' + (pageSizeOptsRender[j] == BackboneGrid.currentState.ps ? ' class="active"' : '') + '>';
                    pageSizeHtml += '<a class="js-change-url page-size" href="#">' + pageSizeOptsRender[j] + '</a>';
                    pageSizeHtml += '</li>';
                }

                $('#'+BackboneGrid.id).find('.pagination.pagesize').html(pageSizeHtml);

                var caption = '';
                if (BackboneGrid.currentState.c > 0)
                    caption = BackboneGrid.currentState.c + ' record(s)';
                else
                    caption = 'No data found';
                $('.' + BackboneGrid.id + '-pagination').html(caption);
            }

            /*
            *   public functions
            */
            this.getGridView = function() {
                return gridView;
            }

            this.getRows = function() {
                return rowsCollection;
            }

            this.getCols = function() {
                return columnsCollection;
            }

            this.getSelectedRows = function() {
                return selectedRows;
            }

            this.getGridSkeleton = function() {
                return BackboneGrid;
            }

            this.getModalForm = function() {
                return modalForm;
            }

            /*this.afterSelectionChanged = function() {

            }*/
            this.build = function() {
                 _.templateSettings.variable = 'rc';
                this.id = config.id;
                BackboneGrid.id = config.id;
                BackboneGrid.personalize_url = config.personalize_url;
                BackboneGrid.edit_url = config.edit_url;
                BackboneGrid.data_url = config.data_url;
                BackboneGrid.current_filters = {};
                BackboneGrid.quickInputId = '#' + config.id + '-quick-search';
                BackboneGrid.events = config.events;
                BackboneGrid.callbacks = config.callbacks;
                BackboneGrid.modalShowBtnId = '#' + config.id + '-modal-form-show';
                BackboneGrid.modalFormId = '#' + config.id + '-modal-form';
                //personal settings
                var state = config.data.state;
                state.p = parseInt(state.p);
                state.mp = parseInt(state.mp);

                BackboneGrid.currentState = state;
                BackboneGrid.pageSizeOptions = config.page_size_options;
                //check data mode
                if (config.data_mode) {
                    BackboneGrid.data_mode = config.data_mode;
                }

                if (config.local_personalize) {
                    BackboneGrid.local_personalize = config.local_personalize;
                }
                //theader
                BackboneGrid.Collections.ColsCollection.prototype.grid = config.id;
                BackboneGrid.Models.ColModel.prototype.personalize_url = config.personalize_url;

                BackboneGrid.Views.ThView.prototype.template = _.template($('#' + config.id + '-header-template').html());
                BackboneGrid.Views.HeaderView.prototype.el = "#" + config.id + " thead tr";
                //tbody
                BackboneGrid.Views.GridView.prototype.el = "table#" + config.id + " tbody";
                BackboneGrid.Views.RowView.prototype.template = _.template($('#' + config.id + '-row-template').html());
                BackboneGrid.Collections.Rows.prototype.data_url = config.data_url;

                //filtering settings
                BackboneGrid.Views.FilterTextCell.prototype.template = _.template($('#' + config.id + '-text-filter-template').html());
                BackboneGrid.Views.FilterDateRangeCell.prototype.template = _.template($('#' + config.id + '-date-range-filter-template').html());
                BackboneGrid.Views.FilterSelectCell.prototype.template = _.template($('#' + config.id + '-select-filter-template').html());
                BackboneGrid.Views.FilterMultiselectCell.prototype.template = _.template($('#' + config.id + '-multiselect-filter-template').html());
                BackboneGrid.Views.FilterNumberRangeCell.prototype.template = _.template($('#' + config.id + '-number-range-filter-template').html());
                //column visibility checkbox view
                BackboneGrid.Views.ColCheckView.prototype.template = _.template($('#' + config.id + '-col-template').html());
                BackboneGrid.Views.FilterCheckView.prototype.template = _.template($('#' + config.id + '-filter-check-template').html());
                //mass edit modal view
                if (typeof(BackboneGrid.Views.ModalForm.prototype.el) === 'undefined')
                    BackboneGrid.Views.ModalForm.prototype.el = BackboneGrid.modalFormId + " .modal-body";

                BackboneGrid.Views.ModalElement.prototype.template = _.template($('#' + config.id + '-modal-element-template').html());
                BackboneGrid.Views.ModalMassGridElement.prototype.template = _.template($('#'+ config.id + '-element-mass-edit').html());

                $('#'+BackboneGrid.id).find('ul.pagination.page').on('click', 'li', function (ev) {
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
                    if (config.data_mode == 'local') {
//                        rowsCollection.sortLocalData();
                        gridView.render();

                        rowsCollection.saveLocalState();
                    } else {
                        rowsCollection.fetch({reset: true});
                    }

                    ev.preventDefault();
                    return;
                });

                //header view
                var columns = config.columns;
                columnsCollection = new BackboneGrid.Collections.ColsCollection;
                var filters = config.filters;

                for (var i in columns) {
                    var c = columns[i];
                    //if (c.name != 'id') {
                    if (c.hidden === 'false')
                        c.hidden = false;
                    if (c.name === 0) {
                        columnsCollection.append = 2;
                    }

                    c.id = config.id + '-' + c.name;
                    //c.style = c['width'] ? "width:"+c['width']+"px" : '';

                    c.cssClass = '';
                    if (!c['no_reorder'])
                        c.cssClass += 'js-draggable ';

                    if (state['s'] && c['name'] && state['s'] == c['name']) {
                        //c.cssClass += 'sort-'+state['sd']+' ';
                        c.sortState = state['sd'];
                    } else {
                        //c.cssClass += 'sort';
                        c.sortState = "";
                    }

                    if (BackboneGrid.validation !== true && typeof(c.validation) !== 'undefined')
                        BackboneGrid.validation = true;
                    if (typeof(c.default) !== 'undefined') {
                        BackboneGrid.Models.Row.prototype.defaults[c.name] = c.default;
                    } else {
                        BackboneGrid.Models.Row.prototype.defaults[c.name] = '';
                    }
                    var ColModel = new BackboneGrid.Models.ColModel(c);
                    columnsCollection.add(ColModel);
                    // }
                }
                var fCollection = [];
                console.log(filters);
                for (var i in filters) {
                    var filter = filters[i];
                    if (typeof(filter.type) !== 'undefined') {
                        if (typeof(filters[i].val) !== 'undefined' && filters[i].val !== '') {
                            var temp = _.clone(filters[i]);
                            var field = temp.field;
                            delete temp.field;
                            BackboneGrid.current_filters[field] = temp;
                        }
                        var c = columnsCollection.findWhere({name: filter.field});
                        if (c) {
                            c.filter_type = filter.type;
                            if (filter.type === 'text') {
                                if (typeof(filter.op) === 'undefined')
                                    filter.op = 'contains';
                                if (typeof(filter.filterLabel) === 'undefined')
                                    filter.filterLabel = 'Contains';
                            }

                            if (filter.type === 'date-range' || filter.type === 'number-range') {
                                if (typeof(filter.op) === 'undefined')
                                    filter.op = 'between';
                                if (typeof(filter.filterLabel) === 'undefined')
                                    filter.filterLabel = 'Between';
                            }

                            if (filter.type === 'multiselect' || filter.type === 'select') {
                                if (typeof(filter.options) === 'undefined') {
                                    filter.options = c.get('options');
                                }
                            }
                            filter.label = c.get('label');
                            fCollection.push(filter);
                        }
                    }
                }

                headerView = new BackboneGrid.Views.HeaderView({collection: columnsCollection});
                headerView.render();
                var colsVisibilityView = new BackboneGrid.Views.ColsVisibilityView({collection: columnsCollection});
                colsVisibilityView.render();

                filtersCollection = new BackboneGrid.Collections.FilterCollection(fCollection);
                filterView = new BackboneGrid.Views.FilterView({collection: filtersCollection});
                filterView.render();
                var windowWidth = $(window).width();
                ////fix when dropdown menu be hidden when reach right side windows
                $(filterView.el).find('div.dropdown.f-grid-filter').on('show.bs.dropdown', function() {
                    var ulEle = $(this).find('ul.dropdown-menu:first');
                    if ($(this).offset().left + ulEle.width() > windowWidth) {
                        ulEle.css({'right' : 0, 'left' : 'auto'});
                    }
                });


                var filtersVisibilityView = new BackboneGrid.Views.FiltersVisibilityView({collection: filtersCollection});
                filtersVisibilityView.render();

                $("ul.filters." + BackboneGrid.id).sortable({
                    handle: '.dd-handle',
                    revert: true,
                    axis: 'y',
                    update: function (event, ui) {
                        filtersVisibilityView.orderChanged();
                    }
                });

                $("ol.columns." + BackboneGrid.id).sortable({
                    handle: '.dd-handle',
                    revert: true,
                    axis: 'y',
                    update: function (event, ui) {
                        colsVisibilityView.orderChanged();
                    }
                });
                if (BackboneGrid.multiselect_filter) {
                    $('body').click(function (ev) {
                        var _cache = filterView.$el.find('div.js-prevent-close');
                        // checking whether opened multiselect filter is exist and clicked element is not opend multilselect filter div
                        if (_cache.length > 0 && $(ev.target).parents('div.js-prevent-close').length === 0) {
                            _cache.find('ul.filter-box').css('display', '');
                            _cache.removeClass('js-prevent-close');
                        }
                    });
                }
                //body view
                var rows = config.data.data;
                rowsCollection = new BackboneGrid.Collections.Rows;

                //showing selected rows count
                selectedRows = new Backbone.Collection;
                var multiselectCol = columnsCollection.findWhere({type: 'row_select'});
                selectedRows.on('add remove reset', function (ev) {
                    multiselectCol.set('selectedCount', selectedRows.length);
                    //@todo: fix loop forever when add selected items from inside form, need check this carefully and ask Boris other solutions, or need refactor this
                    multiselectCol.trigger('render');
                    if (selectedRows.length > 0) {
                        $(BackboneGrid.MassDeleteButton).removeClass('disabled');
                        $(BackboneGrid.MassEditButton).removeClass('disabled');
                    } else {
                        $(BackboneGrid.MassDeleteButton).addClass('disabled');
                        $(BackboneGrid.MassEditButton).addClass('disabled');
                    }

                    if (typeof(gridView.afterSelectionChanged) === 'function')
                        gridView.afterSelectionChanged();

                    return true;
                });

                for (var i in rows) {

                    var rowModel = new BackboneGrid.Models.Row(rows[i]);
                    rowsCollection.add(rowModel, {silent: true});//important, don't delete
                }

                gridView = new BackboneGrid.Views.GridView({collection: rowsCollection});

                //TODO: find other solutions when state.p=NaN and state.mp=NaN.
                if (BackboneGrid.data_mode == 'local' && isNaN(state.p)) {
                    BackboneGrid.currentState.p = 1;
                    BackboneGrid.currentState.ps = 10;
                }
                gridView.render();


                $('#'+BackboneGrid.id).find('ul.pagination.pagesize').on('click', 'a', function (ev) {
                    $('#'+BackboneGrid.id).find('ul.pagination.pagesize li').removeClass('active');
                    BackboneGrid.currentState.ps = parseInt($(this).html());
                    BackboneGrid.currentState.p = 1;
                    //@Todo: fixed, but should find better solutions for backbonegrid
                    if (typeof (config.data_url) !== 'undefined' && config.data_mode != 'local') {
                        rowsCollection.fetch({reset: true});
                    }
                    if (config.data_mode == 'local') {
                        gridView.render();
                        rowsCollection.saveLocalState();
                    }
                    $(this).parents('li:first').addClass('active');
                    ev.preventDefault();

                    return false;

                });

                //action logic
                BackboneGrid.MassDeleteButton = '#' + config.id + ' button.grid-mass-delete';
                BackboneGrid.AddButton = '#' + config.id + ' button.grid-add';
                BackboneGrid.MassEditButton = '#' + config.id + ' a.grid-mass-edit';
                BackboneGrid.NewButton = (typeof(config.new_button) !== 'undefined') ? config.new_button :'Div #' + config.id + ' button.grid-new';
                BackboneGrid.RefreshButton = '#' + config.id + ' .grid-refresh';
                BackboneGrid.ExportButton = '#' + config.id + ' button.grid-export';

                //if ($(BackboneGrid.AddButton).length > 0 || $(BackboneGrid.MassEditButton).length > 0) {
                modalForm = new BackboneGrid.Views.ModalForm({collection: columnsCollection});
                //}

                if ($(BackboneGrid.ExportButton).length > 0) {
                    $(BackboneGrid.ExportButton).on('click', function (ev) {

                        if (typeof(BackboneGrid.data_url) !== '') {
                            window.location.href = rowsCollection.url() + '&export=true';
                        }
                    });
                }

                if ($(BackboneGrid.RefreshButton).length > 0) {
                    $(BackboneGrid.RefreshButton).on('click', function (ev) {
                        if (BackboneGrid.data_mode === 'server') {
                            rowsCollection.fetch({reset: true});
                        } else {
                            gridView.render();
                        }

                        ev.stopPropagation();
                        ev.preventDefault();

                        return false;
                    });
                }

                if ($(BackboneGrid.NewButton).length > 0) {
                    $(BackboneGrid.NewButton).on('click', function (ev) {
                        if ($(this).hasClass('_modal')) {
                            modalForm.modalType = 'addable';
                            modalForm.render();
                            $(BackboneGrid.modalShowBtnId).trigger('click');
                        } else {
                            var newRow = new BackboneGrid.Models.Row({id: guid(), _new: true});
                            rowsCollection.add(newRow);
                            //gridView.render();
                        }
                    });
                }

                if ($(BackboneGrid.MassEditButton).length > 0) {
                    $(BackboneGrid.MassEditButton).on('click', function (ev) {
                        modalForm.modalType = 'mass-editable';
                        modalForm.render();
                        $(BackboneGrid.modalShowBtnId).trigger('click');
                    });
                }

                if ($(BackboneGrid.MassDeleteButton).length > 0) {
                    $(BackboneGrid.MassDeleteButton).on('click', function () {


                        var confirm;
                        if ($(this).hasClass('noconfirm'))
                            confirm = true;
                        else
                            confirm = window.confirm("Do you really want to delete selected rows?");

                        if (confirm) {

                            if (BackboneGrid.data_mode !== 'local') {
                                var ids = selectedRows.pluck('id').join(",");
                                $.post(BackboneGrid.edit_url, {id: ids, oper: 'mass-delete'})
                                    .done(function (data) {
                                        $.bootstrapGrowl("Successfully deleted.", { type: 'success', align: 'center', width: 'auto' });
                                        if (BackboneGrid.data_mode !== 'local')
                                            rowsCollection.fetch({reset: true});
                                        gridView.render();
                                    });
                            }

                            rowsCollection.remove(selectedRows.models, {silent: true});
                            $('select.' + config.id + '.js-sel').val('');
                            if (config.data_mode == 'local') {
                                gridView.render({deleteRows: {models: selectedRows.models}})
                            } else {
                                gridView.render();
                            }
                            selectedRows.reset();
                        }
                    });
                }

                /*if ($(BackboneGrid.AddButton).length > 0) {
                    $(BackboneGrid.AddButton).on('click', function (ev) {

                        ev.preventDefault();
                        ev.stopPropagation();

                        return false;
                    });
                }*/

                //validation
                /*if (BackboneGrid.validation === true) {
                    gridView.form = gridView.$el.parents('form:first');

                    gridView.form.submit(function(ev) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        if(!gridView.form.valid()) {


                            return false;
                        }

                        return true;
                    });
                }*/


                //quick search
                var quickInputId = '#' + config.id + '-quick-search';

                $(quickInputId).keypress(function (ev) {
                    var k = ev.keyCode || ev.which;
                    if (k == 13) {
                        ev.preventDefault();
                        ev.stopPropagation();

                        if (BackboneGrid.data_mode !== 'local') {
                            BackboneGrid.current_filters['_quick'] = $(ev.target).val();
                            rowsCollection.fetch({reset: true});
                        }
                        return false;
                    }
                    return true;
                });
                var restricts = ['FCom/PushServer/index.php', 'media/grid/upload', 'my_account/personalize'];
                //ajax loading...
                $(document).ajaxSend(function (event, jqxhr, settings) {
                    var url = settings.url;
                    for (var i in restricts) {
                        if (url.indexOf(restricts[i]) !== -1)
                            return;
                    }
                    //NProgress.start();
                });
                $(document).ajaxComplete(function (event, jqxhr, settings) {
                    var url = settings.url;
                    for (var i in restricts) {
                        if (url.indexOf(restricts[i]) !== -1)
                            return;
                    }
                    //NProgress.done();
                });
                //NProgress.done();



                setModalHeight();
                updatePageHtml();
            }


            if (typeof(config.grid_before_create) !== 'undefined') {
                window[config.grid_before_create](this);
            } else {
                this.build();
            }

        }
    }
);
