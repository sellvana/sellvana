/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'griddle.fcomGridBody', 'griddle.fcomModalForm', 'griddle.fcomGridFilter', 'fcom.components', 'griddle.custom', 'bootstrap', 'unique'],
function (_, React, $, FComGridBody, FComModalForm, FComFilter, Components, Griddle) {

    /**
     * FCom Griddle Componnent
     */
    var FComGriddleComponent = React.createClass({
        displayName: "FComGriddleComponent",
        getDefaultProps: function () {
            return {
                "config": {},
                "tableClassName": 'fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable',
                "callbacks": {}
            };
        },
        componentWillMount: function () {
            this.initColumn();
        },
        shouldComponentUpdate: function(nextProps, nextState) {
            return !_.isEqual(this.props.config, nextProps.config);
        },
        initColumn: function () { //todo: almost useless, need to re-check this function
            var columnsConfig = this.props.config.columns;

            var all = _.pluck(columnsConfig, 'name');
            var hide = _.pluck(_.filter(columnsConfig, function(column) { return column.hidden == 'true' || column.hidden === true; }), 'name');
            var show = _.difference(all, hide);

            this.props.columns = {all: all, show: show, hide: hide};
            //console.log('this.props.columns', this.props.columns);
            this.props.columnMetadata = columnsConfig;
        },
        getColumn: function (type) {
            switch (type) {
                case 'hide':
                    return this.props.columns.hide;
                    break;
                case 'show':
                    return this.props.columns.show;
                    break;
                default:
                    return this.props.columns.all;
                    break;
            }
        },
        render: function () {
            console.log('griddle.config-' + this.props.config.id, this.props.config);
            var config = this.props.config;

            //prepare props base on data mode
            var props, state;
            if (config.data_mode == 'local') {
                props = {
                    getExternalResults: null,
                    results: config.data.data
                };
                //state = config.state;
            } else {
                props = {
                    getExternalResults: serverMethods.getResults,
                    addRowsExternal: serverMethods.addRows,
                    removeRowsExternal: serverMethods.removeRows,
                    updateRowsExternal: serverMethods.updateRows,
                    results: []
                };
                //state = config.data.state;
            }

            state = config.data.state;

            //set initial page, use for personalization
            var initPage = state.p - 1;
            if (isNaN(initPage) || initPage < 0) {
                initPage = 0;
            }

            return (
                React.createElement("div", {className: "fcom-htmlgrid responsive-table"}, 
                
                    React.createElement(Griddle, React.__spread({showTableHeading: false, tableClassName: this.props.tableClassName, ref: config.id, 
                        config: config, initColumns: this.getColumn(), 
                        sortColumn: state.s, sortAscending: state.sd == 'asc', 
                        columns: this.getColumn('show'), columnMetadata: this.props.columnMetadata, 
                        useCustomGrid: true, customGrid: FComGridBody, 
                        resultsPerPage: state.ps, 
                        useCustomPager: "true", customPager: FComPager, initPage: initPage, 
                        showSettings: true, useCustomSettings: true, customSettings: FComSettings, 
                        showFilter: true, useCustomFilter: "true", customFilter: FComFilter, filterPlaceholderText: "Quick Search"}, 
                        props)
                    )
                )
            );
        }
    });

    var serverMethods = {
        /**
         * build url to submit/get data
         * @param dataUrl
         * @param gridId
         * @param filterString
         * @param sortColumn
         * @param sortAscending
         * @param page
         * @param pageSize
         * @returns {string}
         */
        postUrl: function(dataUrl, gridId, filterString, sortColumn, sortAscending, page, pageSize) {
            var beginQueryChar = (dataUrl.indexOf('?') != -1) ? '&' : '?';
            var sortType = '';
            if (sortColumn != '') {
                sortType = (sortAscending ? 'asc' : 'desc');
            }
            return dataUrl + beginQueryChar + 'gridId=' + gridId + '&p=' + (page + 1) + '&ps=' + pageSize + '&s=' + sortColumn + '&sd=' + sortType + '&filters=' + (filterString ? filterString : '{}');
        },
        /**
         * get data from external results
         * @param filterString
         * @param sortColumn
         * @param sortAscending
         * @param page
         * @param pageSize
         * @param callback
         * @param options
         */
        getResults: function(filterString, sortColumn, sortAscending, page, pageSize, callback, options) {
            $.ajax({
                url: serverMethods.postUrl(options.dataUrl, options.gridId, filterString, sortColumn, sortAscending, page, pageSize),
                dataType: 'json',
                type: 'GET',
                data: {},
                success: function (response) {
                    var data = {
                        results: response[1],
                        totalResults: response[0].c
                    };

                    callback(data);
                },
                error: function (xhr, status, err) {
                    //console.error(this.props.url, status, err.toString());
                }
            });
        },
        /**
         * add rows to external results
         * @param rows
         * @param {function} triggerEvent
         */
        addRows: function(rows, triggerEvent) {
            console.log('addRowsExternal');
            triggerEvent();
        },
        /**
         * remove rows in external results
         * @param rows
         * @param triggerEvent
         */
        removeRows: function(rows, triggerEvent) {
            console.log('removeRowsExternal');
            triggerEvent();
        },
        /**
         * update rows in external results
         * @param rows
         * @param triggerEvent
         */
        updateRows: function(rows, triggerEvent) {
            console.log('updateRowsExternal');
            triggerEvent();
        }
    };

    /**
     * FCom Pager component
     */
    var FComPager = React.createClass({displayName: "FComPager",
        getDefaultProps: function () {
            return {
                "maxPage": 0,
                "nextText": "",
                "previousText": "",
                "currentPage": 0,
                "getHeaderSelection": null,
                "totalResults": 0
            }
        },
        pageChange: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(event.target.getAttribute("data-value")));
        },
        pageFirst: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(0));
        },
        pageNext: function (event) {
            event.preventDefault();
            this.props.next();
        },
        pagePrevious: function (event) {
            event.preventDefault();
            this.props.previous();
        },
        pageLast: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(this.props.maxPage) - 1);
        },
        setPageSize: function (event) {
            event.preventDefault();
            var value = event.target.dataset.value;
            //pageSize = parseInt(value);

            this.props.setPageSize(parseInt(value));
            this.props.setPage(0);
        },
        calcPageSizeOptionsForRender: function(pageSizeOptions) {
            var pageSizeOptsRender = [];
            for (var j = 0; j < pageSizeOptions.length; j++) {
                var value = pageSizeOptions[j];
                pageSizeOptsRender.push(value);
                if (this.props.totalResults <= value) {
                    break;
                }
            }

            return pageSizeOptsRender;
        },
        pageGoTo: function (event) {
            event.preventDefault();
            if (event.keyCode == 13) {
                var startIndex = Math.max(this.props.currentPage - this.props.maxPage, 0),
                    endIndex = Math.min(startIndex + this.props.maxPage, this.props.maxPage),
                    num = event.target.value;

                if (parseInt(num) <= startIndex || num.match(/\D/g)) {
                    event.target.value = num = startIndex + 1;
                } else if(parseInt(num) > endIndex) {
                    event.target.value = num = endIndex;
                }
                num = parseInt(num);
                this.props.setPage((num == 1) ? 0 : num - 1);
            }
        },
        render: function () {
            var headerSelection = this.props.getHeaderSelection();
            if (headerSelection == 'show_selected') {
                return false;
            }
            var pageSizeOptions = this.props.getConfig('page_size_options');
            var pageSize = this.props.resultsPerPage;

            var disabledClass = !this.props.totalResults ? ' disabled' : '';

            var pageGoTo = React.createElement("input", {type: "text", defaultValue: "", className: 'f-grid-page-no form-control', onKeyUp: this.pageGoTo})

            var first = React.createElement("li", {className: 'first' + disabledClass}, 
                React.createElement("a", {href: "#", className: "js-change-url", onClick: this.pageFirst}, "«")
            );
            var previous = React.createElement("li", {className: 'prev' + disabledClass}, 
                React.createElement("a", {href: "#", className: "js-change-url", onClick: this.pagePrevious}, "‹")
            );
            var next = React.createElement("li", {className: 'next' + disabledClass}, 
                React.createElement("a", {className: "js-change-url", href: "#", onClick: this.pageNext}, "›")
            );
            var last = React.createElement("li", {className: 'last' + disabledClass}, 
                React.createElement("a", {className: "js-change-url", href: "#", onClick: this.pageLast}, this.props.maxPage, " »")
            );

            var options = [];

            var startIndex = Math.max(this.props.currentPage - 3, 0);
            var endIndex = Math.min(startIndex + 7, this.props.maxPage);
            if (this.props.maxPage >= 7 && (endIndex - startIndex) <= 6) {
                startIndex = endIndex - 7;
            }

            for (var i = startIndex; i < endIndex; i++) {
                var selected = this.props.currentPage == i ? "page active" : "page";
                options.push(
                    React.createElement("li", {key: 'fcom-pager-pagenumber-' + i, className: selected}, 
                        React.createElement("a", {href: "#", "data-value": i, onClick: this.pageChange, className: "js-change-url"}, i + 1)
                    )
                );
            }

            var pageSizeHtml = [];
            var pageSizeOptionsForRender = this.calcPageSizeOptionsForRender(pageSizeOptions);
            for (var j = 0; j < pageSizeOptionsForRender.length; j++) {
                selected = (pageSizeOptionsForRender[j] == pageSize ? "active" : "") + disabledClass;
                pageSizeHtml.push(
                    React.createElement("li", {className: selected, key: 'fcom-pager-pagesize-' + pageSizeOptionsForRender[j]}, 
                        React.createElement("a", {href: "#", "data-value": pageSizeOptionsForRender[j], onClick: this.setPageSize, className: "js-change-url page-size"}, pageSizeOptionsForRender[j])
                    )
                );
            }

            return (
                React.createElement("div", {className: "col-sm-6 text-right pagination", style: { margin: "0"}}, 
                    React.createElement("span", {className: "f-grid-pagination"}, this.props.totalResults ? this.props.totalResults + ' record(s)' : 'No data found'), 
                    React.createElement("ul", {className: "pagination pagination-sm pagination-griddle pagesize"}, 
                        pageSizeHtml
                    ), 
                    this.props.maxPage >= 7 ? React.createElement("span", {className: "f-grid-pagination"}, 'Page: ', " ", pageGoTo) : '', 
                    React.createElement("ul", {className: "pagination pagination-sm pagination-griddle page"}, 
                        first, 
                        previous, 
                        options, 
                        next, 
                        last
                    )
                )
            )
        }
    });

    /**
     * FCom Settings component
     */
    var FComSettings = React.createClass({displayName: "FComSettings",
        mixins: [FCom.Mixin],
        getDefaultProps: function() {
            return {
                "className": "",
                "getConfig": null,
                "selectedColumns": [],
                "refresh": null,
                "removeRows": null
            }
        },
        modalSaveMassChanges: function(modal) {
            //todo: combine this with FComGridBody::modalSaveChange()
            var that = this;
            var gridId = this.props.getConfig('id');
            var url = this.props.getConfig('edit_url');
            var ids = _.pluck(this.props.getSelectedRows(), 'id');
            var hash = { oper: 'mass-edit', id: ids.join(',') };
            var isLocalMode = !this.props.hasExternalResults();
            var formType = this.getMassEditFormType();
            var form = $(modal.getDOMNode()).find('form');

            if (formType !== 'form'){
                form = $(modal.getDOMNode()).find('#' + gridId + '-modal-mass-form');

                var attrs = { };

                $.each(form[0].attributes, function(idx, attr) {
                    attrs[attr.nodeName] = attr.nodeValue;
                });

                form = $("<form/>", attrs).append(form.contents());
            }

            form.find('textarea, input, select').each(function() {
                var key = $(this).attr('id');
                var val = $(this).val();
                hash[key] = that.html2text(val);
            });

            form.validate();
            if (form.valid()) {

                if (isLocalMode) {
                    var dataToSave = [];
                    _.each(ids, function(id) {
                        var item = _.clone(hash);
                        item.id = id.toString();
                        dataToSave.push(item);
                    });

                    if (dataToSave.length) {
                        this.props.updateRows(dataToSave);
                    }

                    modal.close();
                } else if (url) {
                    $.post(url, hash, function(data) {
                        if (data) {
                            that.props.refresh();
                            modal.close();
                        } else {
                            alert('error when save');
                            return false;
                        }
                    });
                }
            } else {
                //error
                console.log('form validate fail');
                return false;
            }
        },
        getMassEditFormType: function(){
            var massEditType = this.props.getConfig('mass_edit_type');
            switch (massEditType){
                case 'div':
                    return 'div';
                    break;
                default:
                    return 'form';
                    break;
            }
        },
        doMassAction: function(event) { //top mass action
            /*if (this.props.getConfig('data_mode') == 'local') {
                return this.doMassLocalAction(event);
            }*/
            var that = this;
            var action = event.target.dataset.action;
            var dataUrl = this.props.getConfig('data_url');
            var editUrl = this.props.getConfig('edit_url');
            var gridId = this.props.getConfig('id');
            var isLocalMode = !this.props.hasExternalResults();

            switch (action) {
                case 'mass-delete':
                    var confirm = false;
                    if ($(event.target).hasClass('noconfirm')) {
                        confirm = true;
                    } else {
                        confirm = window.confirm("Do you really want to delete selected rows?");
                    }

                    if (confirm) {
                        if (isLocalMode) {
                            var selectedRows = this.props.getSelectedRows();
                            if (selectedRows.length && this.props.removeRows != null) {
                                this.props.removeRows(selectedRows);
                            }
                        } else {
                            var ids = _.pluck(this.props.getSelectedRows(), 'id').join(',');
                            $.post(dataUrl, { oper: action, id: ids }, function() {
                                that.props.clearSelectedRows();
                                that.props.refresh();
                            });
                        }
                    }

                    break;
                case 'mass-edit': //mass-edit with modal
                    var modalEleContainer = document.getElementById(gridId + '-modal');
                    React.unmountComponentAtNode(modalEleContainer); //un-mount current modal
                    React.render(
                        React.createElement(Components.Modal, {show: true, title: "Mass Edit Form", confirm: "Save changes", cancel: "Close", onConfirm: this.modalSaveMassChanges, isLocalMode: isLocalMode, formType: this.getMassEditFormType()}, 
                            React.createElement(FComModalMassEditForm, {editUrl: editUrl, columnMetadata: this.props.columnMetadata, id: gridId, isLocalMode: isLocalMode, formType: this.getMassEditFormType()})
                        ),
                        modalEleContainer
                    );
                    break;
                case 'export':
                    if (dataUrl != '') {
                        var pageSize = this.props.resultsPerPage;
                        var griddleState = this.props.getGriddleState();
                        var exportUrl = serverMethods.postUrl(dataUrl, gridId, griddleState.filter, griddleState.sortColumn, griddleState.sortAscending, griddleState.page, pageSize);
                        window.location.href = exportUrl + '&export=true';
                    }
                    break;
                default:
                    console.log('do-mass-action');
                    break;
            }

        },
        toggleColumn: function(event) {
            var personalizeUrl = this.props.getConfig('personalize_url');
            var id = this.props.getConfig('id');

            var initColumns = this.props.getInitColumns();
            var selectedColumns = this.props.selectedColumns();
            if(event.target.checked == true && _.contains(selectedColumns, event.target.dataset.name) == false){
                selectedColumns.push(event.target.dataset.name);
                var diff = _.difference(initColumns, selectedColumns);
                if (diff.length > 0) {
                    selectedColumns = initColumns;
                    for(var i=0; i < diff.length; i++) {
                        selectedColumns = _.without(selectedColumns, diff[i]);
                    }
                    this.props.setColumns(selectedColumns);
                } else {
                    this.props.setColumns(initColumns);
                }
            } else {
                /* redraw with the selected initColumns minus the one just unchecked */
                this.props.setColumns(_.without(selectedColumns, event.target.dataset.name));
            }

            if (personalizeUrl) {
                $.post(personalizeUrl, { 'do': 'grid.col.hidden', 'grid': id, 'col': event.target.dataset.name, hidden: !(event.target.checked == true) });
            }

            $(event.target).parents('div.dropdown').addClass('open');
        },
        quickSearch: function(event) {
            this.props.searchWithinResults(event.target.value);
        },
        sortColumns: function(newPosColumns) {
            var personalizeUrl = this.props.getConfig('personalize_url');

            if (personalizeUrl) {
                var id = this.props.getConfig('id');
                var selectedColumns = this.props.selectedColumns();
                var postColumns = [];

                _.forEach(newPosColumns, function(col, index) {
                    postColumns.push({
                        name: col,
                        position: index + 1, //plus 1 because pos 0 always is header-dropdown-selection
                        hidden: !_.contains(selectedColumns, col)
                    })
                });

                $.post(personalizeUrl, { 'do': 'grid.col.orders', 'grid': id, 'cols': JSON.stringify(postColumns) });
            }

            newPosColumns.unshift(0); //add first column again
            this.props.updateInitColumns(newPosColumns);
        },
        componentDidMount: function() {
            var that = this;
            var dom = $(this.getDOMNode()).find('.dd-list');
            dom.sortable({
                handle: '.dd-handle',
                revert: true,
                axis: 'y',
                stop: function () {
                    var newPosColumns = dom.sortable('toArray', {attribute: 'data-id'}); //new position columns array
                    dom.sortable("cancel");
                    that.sortColumns(newPosColumns);
                }
            });
        },
        handleClick: function(event) {
            var that = this;
            var gridId = that.props.getConfig('id');
            if ($(event.target).hasClass('_modal')) {
                var modalEleContainer = document.getElementById(gridId + '-modal');
                React.unmountComponentAtNode(modalEleContainer); //un-mount current modal
                React.render(
                    React.createElement(Components.Modal, {show: true, title: "Create Form", confirm: "Save changes", cancel: "Close", onConfirm: that.props.saveModalForm}, 
                        React.createElement(FComModalForm, {columnMetadata: that.props.columnMetadata, id: gridId})
                    ),
                    modalEleContainer
                );
            } else {
                this.props.addRows([{ id: guid() }]);
            }
        },
        handleCustom: function(callback, event) {
            if (typeof window[callback] === 'function') {
                return window[callback](this.props.getCurrentGrid());
            }
        },
        render: function () {
            var that = this;
            var gridId = this.props.getConfig('id');

            //quick search
            var quickSearch = React.createElement("input", {type: "text", className: "f-grid-quick-search form-control", placeholder: "Search within results", id: gridId + '-quick-search', onChange: this.quickSearch});

            var disabledClass = !this.props.getSelectedRows().length ? ' disabled' : '';
            var configActions = this.props.getConfig('actions');
            var buttonActions = [];
            if (configActions) {
                _.forEach(configActions, function(action, name) {
                    var node = '';
                    var actionKey = gridId + '-fcom-settings-action-' + name;
                    var actionProps = {
                        key: gridId + '-fcom-settings-action-' + name,
                        className: action.class
                    };
                    switch (name) {
                        case 'refresh':
                            node = React.createElement("a", React.__spread({href: "#"},  actionProps), action.caption);
                            break;
                        case 'export':
                            node = React.createElement("button", React.__spread({},  actionProps, {"data-action": "export", onClick: that.doMassAction}), action.caption);
                            break;
                        case 'link_to_page':
                            node = React.createElement("a", React.__spread({href: "#"},  actionProps), action.caption);
                            break;
                        case 'edit':
                            actionProps.disabled = disabledClass;
                            node = React.createElement("a", React.__spread({href: "#"},  actionProps, {"data-action": "mass-edit", onClick: that.doMassAction, role: "button"}), action.caption);
                            break;
                        case 'delete':
                            actionProps.disabled = disabledClass;
                            node = React.createElement("button", React.__spread({type: "button"},  actionProps, {"data-action": "mass-delete", onClick: that.doMassAction}), action.caption);
                            break;
                        //todo: checking again new and add type
                        case 'add':
                            node = React.createElement("button", React.__spread({},  actionProps, {type: "button"}), action.caption);
                            break;
                        case 'new':
                            node = React.createElement("button", React.__spread({},  actionProps, {onClick: that.handleClick, type: "button"}), action.caption);
                            break;
                        default:
                            if (action.type) {
                                switch (action.type) {
                                    case 'button':
                                    default:
                                        //compatibility with old backbone grid
                                        
                                        node = React.createElement("button", {className: action.class + (action.isMassAction ? disabledClass : ''), key: actionKey, id: action.id, 
                                            type: "button", onClick: that.handleCustom.bind(null, action.callback)}, action.caption);
                                        break;
                                }
                            } else if (action.html) {
                                node = React.createElement("span", {key: actionKey, dangerouslySetInnerHTML: {__html: action.html}});
                            }

                            break;
                    }

                    buttonActions.push(node);
                });
            }

            var options = _.map(this.props.getInitColumns(), function(column) {
                if (column == '0') {
                    return false;
                }

                var checked = _.contains(that.props.selectedColumns(), column);
                var colInfo = _.findWhere(that.props.columnMetadata, {name: column});

                //not render options item element in case no LABEL
                if (typeof colInfo == 'undefined' || colInfo.label == '') {
                    return false;
                }

                return (
                    React.createElement("li", {"data-id": column, id: column, key: gridId + '-fcom-settings-' + column, className: "dd-item dd3-item"}, 
                        React.createElement("div", {className: "icon-ellipsis-vertical dd-handle dd3-handle"}), 
                        React.createElement("div", {className: "dd3-content"}, 
                            React.createElement("label", null, 
                                React.createElement("input", {type: "checkbox", defaultChecked: checked, "data-id": column, "data-name": column, className: "showhide_column", onChange: that.toggleColumn}), 
                                colInfo ?  colInfo.label : column
                            )
                        )
                    )
                )
            });

            var styleColumnSettings = {position: 'absolute', top: 'auto', marginTop: '-2px', padding: '0', display: 'block', left: 0};
            return (
                React.createElement("div", {className: "col-sm-6"}, 
                    quickSearch, 
                    React.createElement("div", {className: "dropdown dd dd-nestable columns-span", style: { display: 'inline'}}, 
                        React.createElement("a", {href: "#", className: "btn dropdown-toggle showhide_columns", "data-toggle": "dropdown"}, 
                            "Columns ", React.createElement("b", {className: "caret"})
                        ), 
                        React.createElement("div", {id: "column-settings", style: styleColumnSettings}, 
                            React.createElement("ol", {className: "dd-list dropdown-menu columns ui-sortable", style: {minWidth: '200px'}}, 
                                options
                            )
                        )
                    ), 
                    buttonActions
                )
            )
        }
    });

    /**
     * FCom Modal Mass Edit Form
     */
    var FComModalMassEditForm = React.createClass({
        displayName: "FComModalMassEditForm",
        getInitialState: function() {
            var fields = [];
            var shownFields = [];
            var oneField = false
            _.forEach(this.props.columnMetadata, function(column) {
                if (column['multirow_edit']) {
                    fields.push(column);
                }
            });

            if (fields.length == 1) {
                shownFields = [fields[0].name];
                oneField = true;
            }

            return {
                'shownFields': shownFields,
                'fields': fields,
                'oneField': oneField
            }
        },
        getDefaultProps: function() {
            return {
                'columnMetadata': [],
                'editUrl': '',
                'id': 'modal-mass-form'
            };
        },
        componentDidMount: function() {
            var that = this;
            var domNode = this.getDOMNode();
            var select = $(domNode).find('.well select');
            select.select2({
                placeholder: "Select a Field",
                allowClear: true
            });
            select.on('change', function(e) {
                that.addField(e);
                $(this).select2('data', null);
            });
        },
        addField: function(event) { //render field is selected in dropdown
            if (event.target.value != '') {
                var shownFields = this.state.shownFields;
                shownFields.push(event.target.value);
                this.setState({shownFields: shownFields});
            }
        },
        removeField: function(event) {
            var fieldName = event.target.dataset.field;
            console.log('removeField.field', fieldName);
            console.log('removeField.dataset', event.target.dataset);
            if (fieldName && _.contains(this.state.shownFields, fieldName)) {
                var shownFields = _.without(this.state.shownFields, fieldName);
                this.setState({shownFields: shownFields});
            }
        },
        render: function() {
            console.log('state.fields', this.state.fields);
            console.log('state.shownFields', this.state.shownFields);
            //todo: we have 2 types of render mass-edit, refer https://fulleron.atlassian.net/browse/SC-306

            //if (!this.props.editUrl) return null;
            var that = this;
            var gridId = this.props.id;
            var oneField = this.state.oneField;

            var formType = this.props.formType;

            var fieldDropdownDiv = null;

            if (!oneField) {
                var fieldDropDownNodes = this.state.fields.map(function(column) {
                    if (!_.contains(that.state.shownFields, column.name)) {
                        return React.createElement("option", {value: column.name}, column.label);
                    }
                    return null;
                });
                fieldDropDownNodes.unshift(React.createElement("option", {value: ""}));

                fieldDropdownDiv = (
                    React.createElement("div", { className: "well" },
                        React.createElement("div", { className: "row" },
                            React.createElement("div", { className: "col-sm-12" },
                                React.createElement("select", { className: "select2 form-control", id: gridId + '-form-select', style: { width: '150px' } },
                                    fieldDropDownNodes
                                )
                            )
                        )
                    )
                );
            }

            var formElements = this.state.shownFields.map(function(fieldName) {
                var column = _.findWhere(that.state.fields, {name: fieldName});
                return React.createElement(Components.ModalElement, {column: column, removeFieldDisplay: !oneField, removeFieldHandle: that.removeField})
            });

            return (
                React.createElement("div", null,
                    fieldDropdownDiv,
                    React.createElement(formType, {className: "form form-horizontal validate-form", id: gridId + '-modal-mass-form'},
                        formElements
                    )
                )
            );
        }
    });

    return FComGriddleComponent;
});
