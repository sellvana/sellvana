/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'griddle.fcomGridBody', 'griddle.fcomGridFilter', 'fcom.components', 'griddle.custom', 'backbone', 'bootstrap'],
function (_, React, $, FComGridBody, FComFilter, Components, Griddle, Backbone) {

    var dataUrl,
        gridId,
        buildGridDataUrl = function (filterString, sortColumn, sortAscending, page, pageSize) {
            return dataUrl + '?gridId=' + gridId + '&p=' + (page + 1) + '&ps=' + pageSize + '&s=' + sortColumn + '&sd=' + sortAscending + '&filters=' + (filterString ? filterString : '{}');
        };

    var FComGriddleComponent = React.createClass({displayName: "FComGriddleComponent",
        getDefaultProps: function () {
            return {
                "config": {},
                "tableClassName": 'fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable'
            }
        },
        componentWillMount: function () {
            this.initColumn();
            //todo: need change way to get right info
            dataUrl = this.props.config.data_url;
            gridId = this.props.config.id;
        },
        initColumn: function () { //todo: almost useless, need to re-check this function
            var columnsConfig = this.props.config.columns;

            var all = _.pluck(columnsConfig, 'name');
            var hide = _.pluck(_.filter(columnsConfig, function(column) { return column.hidden == 'true' || column.hidden == true }), 'name');
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
            console.log('config', this.props.config);
            var config = this.props.config;

            return (
                React.createElement(Griddle, {showTableHeading: false, tableClassName: this.props.tableClassName, 
                    config: config, initColumns: this.getColumn(), 
                    sortColumn: config.data.state.s, sortAscending: config.data.state.sd == 'asc', 
                    columns: this.getColumn('show'), columnMetadata: this.props.columnMetadata, 
                    useCustomGrid: true, customGrid: FComGridBody, 
                    getExternalResults: FComDataMethod, resultsPerPage: config.data.state.ps, 
                    useCustomPager: "true", customPager: FComPager, initPage: config.data.state.p - 1, 
                    showSettings: true, useCustomSettings: true, customSettings: FComSettings, 
                    showFilter: true, useCustomFilter: "true", customFilter: FComFilter, filterPlaceholderText: "Quick Search"}
                )
            );
        }
    });

    /**
     * callback to get data from external results
     * @param filterString
     * @param sortColumn
     * @param sortAscending
     * @param page
     * @param pageSize
     * @param callback
     * @constructor
     */
    var FComDataMethod = function (filterString, sortColumn, sortAscending, page, pageSize, callback) {
        $.ajax({
            url: buildGridDataUrl(filterString, sortColumn, sortAscending, page, pageSize),
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
        render: function () {
            var headerSelection = this.props.getHeaderSelection();
            if (headerSelection == 'show_selected') {
                return false;
            }
            var pageSizeOptions = this.props.getConfig('page_size_options');
            var pageSize = this.props.resultsPerPage;

            var disabledClass = !this.props.totalResults ? ' disabled' : '';

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

            var startIndex = Math.max(this.props.currentPage - 5, 0);
            var endIndex = Math.min(startIndex + 11, this.props.maxPage);
            if (this.props.maxPage >= 11 && (endIndex - startIndex) <= 10) {
                startIndex = endIndex - 11;
            }

            for (var i = startIndex; i < endIndex; i++) {
                var selected = this.props.currentPage == i ? "page active" : "page";
                options.push(
                    React.createElement("li", {className: selected}, 
                        React.createElement("a", {href: "#", "data-value": i, onClick: this.pageChange, className: "js-change-url"}, i + 1)
                    )
                );
            }

            var pageSizeHtml = [];
            for (var j = 0; j < pageSizeOptions.length; j++) {
                selected = (pageSizeOptions[j] == pageSize ? "active" : "") + disabledClass;
                pageSizeHtml.push(
                    React.createElement("li", {className: selected}, 
                        React.createElement("a", {href: "#", "data-value": pageSizeOptions[j], onClick: this.setPageSize, className: "js-change-url page-size"}, pageSizeOptions[j])
                    )
                );
            }

            return (
                React.createElement("div", {className: "col-sm-6 text-right pagination", style: { margin: "0"}}, 
                    React.createElement("span", {className: "f-grid-pagination"}, this.props.totalResults ? this.props.totalResults + ' record(s)' : 'No data found'), 
                    React.createElement("ul", {className: "pagination pagination-sm pagination-griddle pagesize"}, 
                        pageSizeHtml
                    ), 
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
                "refresh": null
            }
        },
        modalSaveMassChanges: function(modal) {
            //todo: combine this with FComGridBody::modalSaveChange()
            var that = this;
            var url = this.props.getConfig('edit_url');
            if (url) {
                var ids = _.pluck(this.props.getSelectedRows(), 'id').join(',');
                var hash = { oper: 'mass-edit', id: ids };
                var form = $(modal.getDOMNode()).find('form');
                form.find('textarea, input, select').each(function() {
                    var key = $(this).attr('id');
                    var val = $(this).val();
                    hash[key] = that.html2text(val);
                });
                form.validate();
                if (form.valid()) {
                    $.post(url, hash, function(data) {
                        if (data) {
                            that.props.refresh();
                            modal.close();
                        } else {
                            alert('error when save');
                            return false;
                        }
                    });
                } else {
                    //error
                    console.log('error');
                    return false;
                }
            }
        },
        doMassAction: function(event) { //top mass action
            var that = this;
            var action = event.target.dataset.action;
            var dataUrl = this.props.getConfig('data_url');
            var editUrl = this.props.getConfig('edit_url');
            var gridId = this.props.getConfig('id');
            var pageSize = this.props.resultsPerPage;

            switch (action) {
                case 'mass-delete':
                    var confirm = false;
                    if ($(event.target).hasClass('noconfirm')) {
                        confirm = true;
                    } else {
                        confirm = window.confirm("Do you really want to delete selected rows?");
                    }

                    if (confirm) {
                        var ids = _.pluck(this.props.getSelectedRows(), 'id').join(',');
                        $.post(dataUrl, { oper: action, id: ids }, function() {
                            that.props.refresh();
                        });
                    }

                    break;
                case 'mass-edit': //mass-edit with modal
                    var modalEleContainer = document.getElementById(gridId + '-modal');
                    React.unmountComponentAtNode(modalEleContainer); //un-mount current modal
                    React.render(
                        React.createElement(Components.Modal, {show: true, title: "Mass Edit Form", confirm: "Save changes", cancel: "Close", onConfirm: this.modalSaveMassChanges}, 
                            React.createElement(FComModalMassEditForm, {editUrl: editUrl, columnMetadata: this.props.columnMetadata, id: gridId})
                        ),
                        modalEleContainer
                    );
                    break;
                case 'export':
                    var griddleState = this.props.getGriddleState();
                    var exportUrl = buildGridDataUrl(griddleState.filter, griddleState.sortColumn, griddleState.sortAscending, griddleState.page, pageSize);
                    window.location.href = exportUrl + '&export=true';
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

            //don't close dropdown after toggle column
            $(event.target).parents('div.dropdown').addClass('open');
        },
        quickSearch: function(event) {
            this.props.searchWithinResults(event.target.value);
        },
        sortColumns: function() {
            var personalizeUrl = this.props.getConfig('personalize_url');
            var newPosColumns = $(this.getDOMNode()).find('.dd-list').sortable('toArray', {attribute: 'data-id'}); //new position columns array

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
        componentDidUpdate: function() {
            this.renderDropdownColumnsSettings();
            var that = this;
            $(this.getDOMNode()).find('.dd-list').sortable({
                handle: '.dd-handle',
                revert: true,
                axis: 'y',
                stop: function () {
                    that.sortColumns();
                }
            });
        },
        renderDropdownColumnsSettings: function() {
            var that = this;
            var options = _.map(this.props.getInitColumns(), function(column) {
                if (column == '0') {
                    return false;
                }

                var checked = _.contains(that.props.selectedColumns(), column);
                var colInfo = _.findWhere(that.props.columnMetadata, {name: column});
                return (
                    React.createElement("li", {"data-id": column, id: column, className: "dd-item dd3-item"}, 
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

            var mountNode = document.getElementById('column-settings');
            React.unmountComponentAtNode(mountNode);
            React.render(React.createElement("ol", {className: "dd-list dropdown-menu columns ui-sortable", style: {minWidth: '200px'}}, options), mountNode);
        },
        handleCustom: function(callback, event) {
            if (typeof window[callback] === 'function') {
                return window[callback](this.props.getCurrentGrid());
            }
        },
        render: function () {
            var that = this;
            var id = this.props.getConfig('id');

            //quick search
            var quickSearch = React.createElement("input", {type: "text", className: "f-grid-quick-search form-control", placeholder: "Search within results", id: id + '-quick-search', onChange: this.quickSearch});

            var disabledClass = !this.props.getSelectedRows().length ? ' disabled' : '';
            var configActions = this.props.getConfig('actions');
            var buttonActions = [];
            if (configActions) {
                _.forEach(configActions, function(action, name) {
                    var node = '';
                    switch (name) {
                        case 'refresh':
                            node = React.createElement("a", {href: "#", className: action.class}, action.caption);
                            break;
                        case 'export':
                            node = React.createElement("button", {className: action.class, "data-action": "export", onClick: that.doMassAction}, action.caption);
                            break;
                        case 'link_to_page':
                            node = React.createElement("a", {href: "#", className: action.class}, action.caption);
                            break;
                        case 'edit':
                            node = React.createElement("a", {href: "#", className: action.class + disabledClass, "data-action": "mass-edit", onClick: that.doMassAction, role: "button"}, action.caption);
                            break;
                        case 'delete':
                            node = React.createElement("button", {className: action.class + disabledClass, type: "button", "data-action": "mass-delete", onClick: that.doMassAction}, action.caption);
                            break;
                        case 'add':
                            node = React.createElement("button", {className: action.class, type: "button"}, action.caption);
                            break;
                        case 'new':
                            node = React.createElement("button", {className: action.class, type: "button"}, action.caption);
                            break;
                        default:
                            if (action.type) {
                                switch (action.type) {
                                    case 'button':
                                    default:
                                        //compatibility with old backbone grid
                                        node = React.createElement("button", {className: action.class + (action.isMassAction ? disabledClass : ''), id: action.id, 
                                            type: "button", onClick: that.handleCustom.bind(this, action.callback)}, action.caption);
                                        break;
                                }
                            } else if (action.html) {
                                node = React.createElement("span", {dangerouslySetInnerHTML: {__html: action.html}});
                            }

                            break;
                    }

                    buttonActions.push(node);
                });
            }

            var styleColumnSettings = {position: 'absolute', top: 'auto', marginTop: '-2px', padding: '0', display: 'block', left: 0};
            return (
                React.createElement("div", {className: "col-sm-6"}, 
                    quickSearch, 
                    React.createElement("div", {className: "dropdown dd dd-nestable columns-span", style: { display: 'inline'}}, 
                        React.createElement("a", {href: "#", className: "btn dropdown-toggle showhide_columns", "data-toggle": "dropdown"}, 
                            "Columns ", React.createElement("b", {className: "caret"})
                        ), 
                        React.createElement("div", {id: "column-settings", style: styleColumnSettings})
                    ), 
                    buttonActions
                )
            )
        }
    });

    /**
     * FCom Modal Mass Edit Form
     */
    var FComModalMassEditForm = React.createClass({displayName: "FComModalMassEditForm",
        getInitialState: function() {
            var fields = [];
            var shownFields = [];
            _.forEach(this.props.columnMetadata, function(column) {
                if (column.multirow_edit) {
                    fields.push(column);
                }
            });
            /*if (fields.length == 1) {
                shownFields.push(fields[0].name);
            }*/
            return {
                'shownFields': shownFields,
                'fields': fields
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
            $(domNode).find('.well select').select2({
                placeholder: "Select a Field",
                allowClear: true
            });
            $(domNode).find('.well select').on('change', function(e) {
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

            if (!this.props.editUrl) return null;
            var that = this;
            var gridId = this.props.id;

            var fieldDropDownNodes = this.state.fields.map(function(column) {
                if (!_.contains(that.state.shownFields, column.name)) {
                    return React.createElement("option", {value: column.name}, column.label);
                }
                return null;
            });
            fieldDropDownNodes.unshift(React.createElement("option", {value: ""}));

            var formElements = this.state.shownFields.map(function(fieldName) {
                var column = _.findWhere(that.state.fields, {name: fieldName});
                return React.createElement(Components.ModalElement, {column: column, removeFieldDisplay: true, removeFieldHandle: that.removeField})
            });

            return (
                React.createElement("div", null, 
                    React.createElement("div", {className: "well"}, 
                        React.createElement("div", {className: "row"}, 
                            React.createElement("div", {className: "col-sm-12"}, 
                                React.createElement("select", {className: "select2 form-control", id: gridId + '-form-select', style: {width: '150px'}}, 
                                    fieldDropDownNodes
                                )
                            )
                        )
                    ), 
                    React.createElement("form", {className: "form form-horizontal validate-form", id: gridId + '-modal-mass-form'}, 
                        formElements
                    )
                )
            );
        }
    });

    return FComGriddleComponent;
});