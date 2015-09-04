/** @jsx React.DOM */

/**
 * FCom GridBody Component
 */
define(['react', 'griddle.fcomModalForm', 'griddle.fcomRow', 'fcom.components', 'jquery-ui', 'jquery.validate'], function (React, FComModalForm, FComRow, Components) {

    /*
     var React = require('react');
     var GridRowContainer = require('./gridRowContainer.jsx');
     */
    var FComGridBody = React.createClass({ //replace gridBody.jsx
        mixins: [FCom.Mixin],
        getDefaultProps: function () {
            return {
                "data": [],
                "originalData": [],
                "columnMetadata": [],
                "className": ""
            };
        },
        doRowAction: function(callback, event) {
            // Remove focus for prevent re-render modal when keypress
            event.currentTarget.blur();
            
            /*if (this.props.getConfig('data_mode') == 'local') {
                return this.doRowLocalAction(event);
            }*/
            var that = this;
            var action = event.target.dataset.action;
            var rowId = event.target.dataset.row;
            var gridId = this.props.getConfig('id');
            var data = this.props.originalData ? this.props.originalData : this.props.data;
            var isLocalMode = !this.props.hasExternalResults();
            var editUrl = this.props.getConfig('edit_url');
            var row = _.find(data, function(item) {
                if (item.id == rowId || item.id == parseInt(rowId)) {
                    return true;
                }
            });

            if (!row) {
                console.log('cannot find row in grid', row);
                return false;
            }

            switch (action) {
                case 'edit':
                    var modalEleContainer = document.getElementById(gridId + '-modal');
                    React.unmountComponentAtNode(modalEleContainer); //un-mount current modal
                    React.render(
                        <Components.Modal show={true} title="Edit Form" confirm="Save changes" cancel="Close" onConfirm={that.props.saveModalForm}>
                            <FComModalForm columnMetadata={that.props.columnMetadata} row={row} id={gridId} />
                        </Components.Modal>,
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

                            if (editUrl.length > 0 && rowId) {
                                $.post(editUrl, {id: rowId, oper: 'del'}, function() {
                                    that.props.removeSelectedRows([row]);
                                    that.props.refresh();
                                });
                            }
                        }
                    }
                    break;
                default:
                    if (typeof window[callback] === 'function') {
                        return window[callback](row, event);
                    } else {
                        console.log('Do row custom action');
                    }
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

            var title = <FComGridTitle columns={this.props.columns} changeSort={this.props.changeSort} sortColumn={this.props.sortColumn} getConfig={this.props.getConfig}
                sortAscending={this.props.sortAscending} columnMetadata={this.props.columnMetadata} data={this.props.data}
                originalData={this.props.originalData} setHeaderSelection={this.props.setHeaderSelection} getHeaderSelection={this.props.getHeaderSelection}
                addSelectedRows={this.props.addSelectedRows} getSelectedRows={this.props.getSelectedRows}  clearSelectedRows={this.props.clearSelectedRows}
                removeSelectedRows={this.props.removeSelectedRows} saveLocalState={this.saveLocalState}
                ref={'gridTitle'}
            />;

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
                        if (typeof row[value.name] === 'undefined')
                            row[value.name] = value.default;
                    });
                }

                return <FComRow ref={'row'+row.id} row={row} key={'row-' + row.id} index={index} columns={that.props.columns} columnMetadata={that.props.columnMetadata} defaultValues={defaultValues}
                    getConfig={that.props.getConfig} doRowAction={that.doRowAction} removeSelectedRows={that.props.removeSelectedRows}
                    addSelectedRows={that.props.addSelectedRows} getSelectedRows={that.props.getSelectedRows} />;
            });

            return (
                <table className={this.props.className}>
                    {title}
                    <tbody>
                        {nodes}
                    </tbody>
                </table>
            );
        }
    });

    var FComGridTitle = React.createClass({
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
                        <button data-toggle="dropdown" type="button" className="btn btn-default btn-sm dropdown-toggle">
                            <span className="icon-placeholder">
                                <i className="glyphicon glyphicon-list"></i>
                            </span>
                            <span className="title">A</span>&nbsp;<span className="caret"></span>
                        </button>
                    );

                    if (that.props.getHeaderSelection() == 'show_selected') {
                        selectionButtonText = (
                            <button data-toggle="dropdown" type="button" className="btn btn-default btn-sm dropdown-toggle">
                                <span className="icon-placeholder">
                                    <i className="glyphicon glyphicon-th-list"></i>
                                </span>
                                <span className="title">S</span>&nbsp;<span className="caret"></span>
                            </button>
                        );
                    } else if (selectedRows.length) {
                        selectionButtonText = (
                            <button data-toggle="dropdown" type="button" className="btn btn-default btn-sm dropdown-toggle">
                                <span className="icon-placeholder">
                                    <i className="glyphicon glyphicon-check"></i>
                                </span>
                                <span className="title">{selectedRows.length + (selectedRows.length ? ' rows' : ' row')}</span>&nbsp;<span className="caret"></span>
                            </button>
                        );
                    }

                    var headerSelectionNodes = that.getHeaderSelectionOptions().map(function(option) {
                        if ((option.visibleOnSelected && selectedRows.length) ||
                            (option.invisibleOnSelected && !selectedRows.length)) {
                            return (<li key={'0' + option.label}> <a href="#" data-select={option.select} onClick={option.action ? option.action : that.updateHeaderSelect}>{option.label}</a></li>)
                        }
                    });

                    return (
                        <th className={columnClass} data-id="0" key={col}>
                            <div className="dropdown f-grid-display-type">
                                {selectionButtonText}
                                <ul className="dropdown-menu js-sel">{headerSelectionNodes}</ul>
                            </div>
                        </th>
                    );
                }

                if (that.props.sortColumn == col) {
                    columnClass += that.props.sortAscending ? 'sort-ascending th-sorting-asc' : 'sort-descending th-sorting-desc';
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

                if (typeof meta !== "undefined" && (meta.name == 'btn_group' || meta.sortable == false)) {
                    return (
                        <th data-title={col} className={columnClass} style={width} key={col}>
                            {displayName}
                        </th>
                    )
                } else {
                    return (
                        <th onClick={that.sort} data-title={col} className={columnClass} style={width} key={col}>
                            <a href="#" className="js-change-url" onClick={that.triggerSort}>{displayName}</a>
                        </th>
                    );
                }
            });

            return(
                <thead>
                    <tr>
                    {nodes}
                    </tr>
                </thead>
            );
        }
    });

    //module.exports = FComGridBody;
    return FComGridBody;
});
