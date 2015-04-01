/** @jsx React.DOM */

/**
 * FCom GridBody Component
 */
define(['react', 'griddle.fcomRow', 'fcom.components', 'jquery-ui', 'jquery.validate'], function (React, FComRow, Components) {

    /*
     var React = require('react');
     var GridRowContainer = require('./gridRowContainer.jsx');
     */
    var FComGridBody = React.createClass({displayName: "FComGridBody", //replace gridBody.jsx
        mixins: [FCom.Mixin],
        getDefaultProps: function () {
            return {
                "data": [],
                "originalData": [],
                "columnMetadata": [],
                "className": ""
            }
        },
        modalSaveChange: function(modal) {
            //console.log('modalSaveChange');
            var that = this;
            var url = this.props.getConfig('edit_url');

            var hash = { oper: 'edit' };
            var form = $(modal.getDOMNode()).find('form');
            form.find('textarea, input, select').each(function() {
                var key = $(this).attr('id');
                var val = $(this).val();
                hash[key] = that.html2text(val);
            });
            form.validate();
            if (form.valid()) {
                if (this.props.isLocalMode()) {
                    //console.log('localModeSave');
                    this.props.updateRow(hash);
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
                console.log('form validated fail');
                return false;
            }
        },
        doRowAction: function(event) {
            /*if (this.props.getConfig('data_mode') == 'local') {
                return this.doRowLocalAction(event);
            }*/
            var that = this;
            var action = event.target.dataset.action;
            var rowId = event.target.dataset.row;
            var gridId = this.props.getConfig('id');
            var data = this.props.originalData ? this.props.originalData : this.props.data;
            var isLocalMode = this.props.isLocalMode();

            var row = _.find(data, function(item) {
                if (item.id == rowId || item.id == parseInt(rowId)) {
                    return true;
                }
            });

            if (!row) {
                console.log('cannot find row in grid', row);
                return false;
            }


            console.log('action', action);
            switch (action) {
                case 'edit':
                    //console.log('render modal');
                    var modalEleContainer = document.getElementById(gridId + '-modal');
                    React.unmountComponentAtNode(modalEleContainer); //un-mount current modal
                    React.render(
                        React.createElement(Components.Modal, {show: true, title: "Edit Form", confirm: "Save changes", cancel: "Close", onConfirm: this.modalSaveChange}, 
                            React.createElement(FComModalForm, {columnMetadata: that.props.columnMetadata, row: row, id: gridId})
                        ),
                        modalEleContainer
                    );
                    break;
                case 'delete':
                    var confirm = false;
                    if ($(event.target).hasClass('noconfirm')) {
                        confirm = true;
                    } else {
                        confirm = window.confirm("Do you want to really delete?");
                    }

                    if (confirm) {
                        if (isLocalMode) {
                            this.props.removeRows([row]);
                        } else {
                            var editUrl = this.props.getConfig('edit_url');
                            if (editUrl.length > 0 && rowId) {
                                $.post(editUrl, {id: rowId, oper: 'del'}, function() {
                                    that.props.refresh();
                                });
                            }
                        }
                    }
                    break;
                default:
                    console.log('do-row-action');
                    break;
            }
        },
        render: function () {
            var that = this;
            var headerSelection = this.props.getHeaderSelection();
            var selectedRows = this.props.getSelectedRows();
            //console.log('selectedRows', this.props.getSelectedRows());
            //console.log('data', this.props.data);
            //console.log('originalData', this.props.originalData);
            /*console.log('FComGridBody.columnMetadata', this.props.columnMetadata);
            console.log('FComGridBody.columns', this.props.columns);*/

            var title = React.createElement(FComGridTitle, {columns: that.props.columns, changeSort: that.props.changeSort, sortColumn: that.props.sortColumn, getConfig: that.props.getConfig, 
                sortAscending: that.props.sortAscending, columnMetadata: that.props.columnMetadata, data: this.props.data, 
                originalData: this.props.originalData, setHeaderSelection: that.props.setHeaderSelection, getHeaderSelection: this.props.getHeaderSelection, 
                addSelectedRows: this.props.addSelectedRows, getSelectedRows: that.props.getSelectedRows, clearSelectedRows: this.props.clearSelectedRows, 
                removeSelectedRows: this.props.removeSelectedRows, 
                ref: 'gridTitle'}
            );

            //get data for render
            var dataForRender = [];
            if (headerSelection == 'show_selected') { //show only selected rows
                dataForRender = selectedRows;
            } else if (!this.props.hasExternalResults()) { //show data as local mode
                _.forEach(this.props.data, function(row) {
                    var origRow = _.findWhere(that.props.originalData, { id: row.id });
                    dataForRender.push(origRow);
                });
            } else {
                dataForRender = this.props.originalData;
            }

            var defaultValues = [];
            _.forEach(this.props.columnMetadata, function(col, index) {
                if (col.default) {
                    defaultValues.push(col);
                }
            });

            var nodes = dataForRender.map(function (row, index) {
                //set default value
                if (defaultValues.length) {
                    _.forEach(defaultValues, function(value) {
                        row[value.name] = value.default;
                    });
                }

                return React.createElement(FComRow, {ref: 'row'+row.id, row: row, key: 'row-' + row.id, index: index, columns: that.props.columns, columnMetadata: that.props.columnMetadata, defaultValues: defaultValues, 
                    getConfig: that.props.getConfig, doRowAction: that.doRowAction, removeSelectedRows: that.props.removeSelectedRows, 
                    addSelectedRows: that.props.addSelectedRows, getSelectedRows: that.props.getSelectedRows});
            });

            return (
                React.createElement("table", {className: this.props.className}, 
                    title, 
                    React.createElement("tbody", null, 
                        nodes
                    )
                )
            );
        }
    });

    var FComGridTitle = React.createClass({displayName: "FComGridTitle",
        getDefaultProps: function(){
            return {
                "columns":[],
                "sortColumn": "",
                "sortAscending": "",
                "setHeaderSelection": null
            }
        },
        getHeaderSelectionOptions: function() {
            return [
                { select: 'show_all', label: 'Show All', icon: 'list', visibleOnSelected: true },
                { select: 'show_selected', label: 'Show Selected', icon: 'list', visibleOnSelected: true },
                { select: 'select_visible', label: 'Select Visible', icon: 'list', visibleOnSelected: true, invisibleOnSelected: true, action: this.selectVisible },
                { select: 'unselect_visible', label: 'Unselect Visible', icon: 'list', visibleOnSelected: true, action: this.unselectVisible },
                { select: 'unselect_all', label: 'Unselect All', icon: 'list', visibleOnSelected: true, action: this.unselectAll }
            ];
        },
        updateHeaderSelect: function(event) {
            var select = event.target.dataset.select;
            //console.log('operation', select);
            if (!_.findWhere(this.getHeaderSelectionOptions(), {select: select})) {
                select = 'show_all';
            }
            this.props.setHeaderSelection(select);
        },
        sort: function(event){
            if (typeof event.target.dataset.title !== 'undefined') {
                this.props.changeSort(event.target.dataset.title);
            }
        },
        triggerSort: function(event) {
            event.preventDefault();

            var selected = event.target;
            $(selected).parents('th').trigger('click');
        },
        componentDidMount: function() {
            var that = this;
            var gridId = this.props.getConfig('id');
            var personalizeUrl = this.props.getConfig('personalize_url');

            //resize and callback to personalize
            $(this.getDOMNode()).find("th").resizable({
                handles: 'e',
                minWidth: 20,
                stop: function (ev, ui) {
                    if (personalizeUrl) {
                        var width = $(ev.target).width();
                        var postData = { 'do': 'grid.col.width', grid: gridId, col: ev.target.dataset.title, width: width };
                        $.post(personalizeUrl, postData);
                    }
                }
            });
        },
        selectVisible: function(event) {
            var that = this;
            that.props.addSelectedRows(this.props.data);
            event.preventDefault();
        },
        unselectVisible: function(event) {
            this.props.removeSelectedRows(this.props.originalData);
            this.props.setHeaderSelection('show_all');
            event.preventDefault();
        },
        unselectAll: function(event) {
            this.props.clearSelectedRows();
            this.props.setHeaderSelection('show_all');
            event.preventDefault();
        },
        render: function() {
            var that = this;
            var selectedRows = this.props.getSelectedRows();

            var nodes = this.props.columns.map(function(col, index){

                var columnClass = "js-draggable ui-resizable "; //todo: allow resizeable class base on config

                //checkbox
                if (col == '0') {

                    var selectionButtonText = (
                        React.createElement("button", {"data-toggle": "dropdown", type: "button", className: "btn btn-default btn-sm dropdown-toggle"}, 
                            React.createElement("span", {className: "icon-placeholder"}, 
                                React.createElement("i", {className: "glyphicon glyphicon-list"})
                            ), 
                            React.createElement("span", {className: "title"}, "A"), " ", React.createElement("span", {className: "caret"})
                        )
                    );

                    if (that.props.getHeaderSelection() == 'show_selected') {
                        selectionButtonText = (
                            React.createElement("button", {"data-toggle": "dropdown", type: "button", className: "btn btn-default btn-sm dropdown-toggle"}, 
                                React.createElement("span", {className: "icon-placeholder"}, 
                                    React.createElement("i", {className: "glyphicon glyphicon-th-list"})
                                ), 
                                React.createElement("span", {className: "title"}, "S"), " ", React.createElement("span", {className: "caret"})
                            )
                        );
                    } else if (selectedRows.length) {
                        selectionButtonText = (
                            React.createElement("button", {"data-toggle": "dropdown", type: "button", className: "btn btn-default btn-sm dropdown-toggle"}, 
                                React.createElement("span", {className: "icon-placeholder"}, 
                                    React.createElement("i", {className: "glyphicon glyphicon-check"})
                                ), 
                                React.createElement("span", {className: "title"}, selectedRows.length + (selectedRows.length ? ' rows' : ' row')), " ", React.createElement("span", {className: "caret"})
                            )
                        );
                    }

                    var headerSelectionNodes = that.getHeaderSelectionOptions().map(function(option) {
                        if ((option.visibleOnSelected && selectedRows.length) ||
                            (option.invisibleOnSelected && !selectedRows.length)) {
                            return (React.createElement("li", {key: '0' + option.label}, " ", React.createElement("a", {href: "#", "data-select": option.select, onClick: option.action ? option.action : that.updateHeaderSelect}, option.label)))
                        }
                    });

                    return (
                        React.createElement("th", {className: columnClass, "data-id": "0", key: col}, 
                            React.createElement("div", {className: "dropdown f-grid-display-type"}, 
                                selectionButtonText, 
                                React.createElement("ul", {className: "dropdown-menu js-sel"}, headerSelectionNodes)
                            )
                        )
                    );
                }

                if (that.props.sortColumn == col && that.props.sortAscending == 'asc') {
                    columnClass += "sort-ascending th-sorting-asc"
                }  else if (that.props.sortColumn == col && that.props.sortAscending == 'desc') {
                    columnClass += "sort-descending th-sorting-desc"
                }

                var displayName = col;
                var meta;
                if (that.props.columnMetadata != null) {
                    meta = _.findWhere(that.props.columnMetadata, {name: col});
                    if (meta && typeof meta.cssClass != 'undefined') {
                        columnClass += " " + meta.cssClass;
                    }
                    if (typeof meta !== "undefined" && typeof meta.label !== "undefined" && meta.label != null) {
                        displayName = meta.label;
                    }
                }

                var width = meta && meta.width ? {width: meta.width} : {};

                if (typeof meta !== "undefined" && meta.name == 'btn_group') {
                    return (
                        React.createElement("th", {"data-title": col, className: columnClass, key: col}, 
                            displayName
                        )
                    )
                } else {
                    return (
                        React.createElement("th", {onClick: that.sort, "data-title": col, className: columnClass, style: width, key: col}, 
                            React.createElement("a", {href: "#", className: "js-change-url", onClick: that.triggerSort}, displayName)
                        )
                    );
                }
            });

            return(
                React.createElement("thead", null, 
                    React.createElement("tr", null, 
                    nodes
                    )
                )
            );
        }
    });

    /**
     * form content for modal
     */
    var FComModalForm = React.createClass({displayName: "FComModalForm",
        mixins: [FCom.Mixin, FCom.FormMixin],
        getDefaultProps: function () {
            return {
                'row': {},
                'id': 'modal-form',
                'columnMetadata': []
            }
        },
        getInitialState: function () {
            return {
                isNew: (this.props.row.id > 0)
            }
        },
        componentDidMount: function () {
            //console.log('row', this.props.row);
            var that = this;

            //update value for element is rendered as element_print
            $(this.getDOMNode()).find('.element_print').find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                var value = (typeof that.props.row[name] !== 'undefined') ? that.props.row[name] : '';
                $(this).val(that.text2html(value));
            });
        },
        render: function () {
            var that = this;
            var gridId = this.props.id;
            //console.log('row', this.props.row);

            var nodes = this.props.columnMetadata.map(function(column) {
                if( (that.props.row && !column.editable) || (!that.props.row && !column.addable)) return null;
                return React.createElement(Components.ModalElement, {column: column, value: that.props.row[column.name]})
            });

            //add id
            nodes.push(React.createElement("input", {type: "hidden", name: "id", id: "id", value: this.props.row.id}));

            return (
                React.createElement("form", {className: "form form-horizontal validate-form", id: gridId + '-modal-form'}, 
                    nodes
                )
            )
        }
    });

    //module.exports = FComGridBody;
    return FComGridBody;
});