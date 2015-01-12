/** @jsx React.DOM */

/**
 * FCom GridBody Component
 */
define(['react', 'jsx!griddle.fcomRow', 'jsx!fcom.components'], function (React, FComRow, Components) {

    /*
     var React = require('react');
     var GridRowContainer = require('./gridRowContainer.jsx');
     */
    var FComGridBody = React.createClass({ //replace gridBody.jsx
        getDefaultProps: function () {
            return {
                "data": [],
                "columnMetadata": [],
                "className": ""
            }
        },
        componentDidMount: function () {
            //add modal form
            console.log('dom node', this.getDOMNode());
        },
        doButtonAction: function(event) {
            var that = this;
            var action = event.target.dataset.action;
            var rowId = event.target.dataset.row;
            var gridId = this.props.getConfig('id');

            switch (action) {
                case 'edit':
                    console.log('render modal');
                    var row = _.findWhere(this.props.data, {id: rowId});
                    var modalEleContainer = document.getElementById(gridId + '-modal');
                    React.unmountComponentAtNode(modalEleContainer);
                    React.render(
                        <Components.Modal show={true}>
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
                        var editUrl = this.props.getConfig('edit_url');
                        if (editUrl.length > 0 && rowId) {
                            $.post(editUrl, {id: rowId, oper: 'del'}, function() {
                                that.props.refresh();
                            });
                        }
                    }
                    break;
                default:
                    console.log('doButtonAction');
                    break;
            }
        },
        render: function () {
            var that = this;
            /*console.log('FComGridBody.columnMetadata', this.props.columnMetadata);
            console.log('FComGridBody.columns', this.props.columns);*/

            var title = <FComGridTitle columns={that.props.columns} changeSort={that.props.changeSort} sortColumn={that.props.sortColumn} sortAscending={that.props.sortAscending} columnMetadata={that.props.columnMetadata}/>;

            var nodes = this.props.data.map(function (row, index) {
                return <FComRow row={row} index={index} columns={that.props.columns} columnMetadata={that.props.columnMetadata} getConfig={that.props.getConfig} doButtonAction={that.doButtonAction} />
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
                "sortAscending": "asc"
            }
        },
        sort: function(event){
            if (typeof event.target.dataset.title !== 'undefined') {
                this.props.changeSort(event.target.dataset.title);
            }
        },
        triggerSort: function(event){
            event.preventDefault();

            var selected = event.target;
            $(selected).parents('th').trigger('click');
        },
        componentDidMount: function() {
            //$(".dataTable th").resizable({handles: 'e'});
        },
        showAll: function(event) {
            event.preventDefault();

            $(".standard-row").removeClass('hidden');
        },
        showSelected: function(event) {
            event.preventDefault();

            $(".standard-row").each(function() {
                var row = this;

                if ($(row).find(".select-row").is(":checked")) {
                    $(row).removeClass("hidden");
                } else {
                    $(row).addClass("hidden");
                }
            });
        },
        selectVisible: function(event) {
            event.preventDefault();

            $(".standard-row").each(function() {
                    var row = this;
                if ($(row).hasClass('hidden')) {
                    $(row).find(".select-row").prop("checked", false);
                } else {
                    $(row).find(".select-row").prop("checked", true);
                }
            });
        },
        unselectVisible: function(event) {
            event.preventDefault();

            $(".standard-row").each(function() {
                var row = this;
                if (!$(row).hasClass('hidden')) {
                    $(row).find(".select-row").prop("checked", false);
                }
            });
        },
        unselectAll: function(event) {
            event.preventDefault();

            $(".select-row").prop('checked', false);
            $(".standard-row").removeClass('hidden');
        },
        render: function(){
            var that = this;

            var nodes = this.props.columns.map(function(col, index){

                //checkbox
                if (col == '0') {
                    return (
                        <th className="js-draggable ui-resizable" data-id="0">
                            <div className="dropdown f-grid-display-type">
                                <button data-toggle="dropdown" type="button" className="btn btn-default btn-sm dropdown-toggle">
                                    <span className="icon-placeholder">
                                        <i className="glyphicon glyphicon-list"></i>
                                    </span>
                                    <span className="title">A</span>&nbsp;<span className="caret"></span>
                                </button>
                                <ul className="dropdown-menu js-sel">
                                    <li><a href="#" onClick={that.showAll}>Show All</a></li>
                                    <li><a href="#" onClick={that.showSelected}>Show Selected</a></li>
                                    <li><a href="#" onClick={that.selectVisible}>Select Visible</a></li>
                                    <li><a href="#" onClick={that.unselectVisible}>Unselect Visible</a></li>
                                    <li><a href="#" onClick={that.unselectAll}>Unselect All</a></li>
                                </ul>
                            </div>
                        </th>
                    );
                }

                var columnSort = "";

                if (that.props.sortColumn == col && that.props.sortAscending == 'asc'){
                    columnSort += "sort-ascending th-sorting-asc"
                }  else if (that.props.sortColumn == col && that.props.sortAscending == 'desc'){
                    columnSort += "sort-descending th-sorting-desc"
                }

                var displayName = col;
                var meta;
                if (that.props.columnMetadata != null){
                    meta = _.findWhere(that.props.columnMetadata, {name: col});
                    //the weird code is just saying add the space if there's text in columnSort otherwise just set to metaclassname
                    columnSort = meta == null ? columnSort : (columnSort && (columnSort + " ")||columnSort) + meta.cssClass;
                    if (typeof meta !== "undefined" && typeof meta.label !== "undefined" && meta.label != null) {
                        displayName = meta.label;
                    }
                }

                if (typeof meta !== "undefined" && meta.name == 'btn_group') {
                    return (
                        <th data-title={col} className={columnSort}>
                            {displayName}
                        </th>
                    )
                } else {
                    return (
                        <th onClick={that.sort} data-title={col} className={columnSort}>
                            <a href="#" className="js-change-url" onClick={that.triggerSort}> {displayName} </a>
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

    /**
     * form content for modal
     */
    var FComModalForm = React.createClass({
        getDefaultProps: function () {
            return {
                'row': {},
                'id': 'modal-form'
            }
        },
        getInitialState: function () {
            return {
                isNew: (this.props.row.id > 0)
            }
        },
        render: function () {
            var that = this;
            var gridId = this.props.id;

            //console.log('row', this.props.row);

            var nodes = this.props.columnMetadata.map(function(column) {
                if( (that.props.row && !column.editable) || (!that.props.row && !column.addable)) {
                    return null;
                }

                var label = '';
                if (typeof(column.form_hidden_label) === 'undefined' || !column.form_hidden_label) {
                    label = (
                        <div className="control-label col-sm-3">
                            <label for={column.name}>
                                {column.label}
                            </label>
                        </div>
                    );
                }

                var input = '';
                if (typeof column.element_print != 'undefined') { //custom html for element_print
                    input = '<div class="form-group"><div class="control-label col-sm-3"><label for='+column.name+'>'+column.label+'</label></div>';
                    input += '<div class="controls col-sm-8">' + column.element_print + '</div></div>';
                    return <div className="form-group" dangerouslySetInnerHTML={{__html: input}}></div>
                } else {
                    switch (column.editor) {
                        case 'select':
                            var options = [];
                            _.forEach(column.options, function(text, value) {
                                options.push(<option value={value} defaultValue={that.props.row[column.name]}>{text}</option>);
                            });
                            input = <select name={column.name} id={column.name} className="form-control">{options}</select>;
                            //console.log('options', options);
                            break;
                        case 'textarea':
                            input = <textarea name={column.name} id={column.name} className="form-control" rows="5" defaultValue={that.props.row[column.name]} />;
                            //console.log(column.name, that.props.row[column.name]);
                            break;
                        default:
                            input = <input name={column.name} id={column.name} className="form-control" defaultValue={that.props.row[column.name]} />;
                            //console.log(column.name, that.props.row[column.name]);
                            break;
                    }
                }

                return (
                    <div className="form-group">
                        {label} <div className="controls col-sm-8">{input}</div>
                    </div>
                )
            });


            return (
                <form className="form form-horizontal validate-form" id={gridId + '-modal-form'}>
                    {nodes}
                </form>
            )
        }
    });

    //module.exports = FComGridBody;
    return FComGridBody;
});