/** @jsx React.DOM */

/**
 * FCom GridBody Component
 */
define(['react', 'jsx!griddle.fcomRow'], function (React, FComRow) {
    /*
     var React = require('react');
     var GridRowContainer = require('./gridRowContainer.jsx');
     */
    var FComGridBody = React.createClass({ //replace gridBody.jsx
        getDefaultProps: function () {
            return {
                "data": [],
                "metadataColumns": [],
                "className": ""
            }
        },
        render: function () {
            var that = this;

            var title = <FComGridTitle columns={that.props.columns} changeSort={that.props.changeSort} sortColumn={that.props.sortColumn} sortAscending={that.props.sortAscending} columnMetadata={that.props.columnMetadata}/>;

            var nodes = this.props.data.map(function (row, index) {
                return <FComRow data={row} index={index} metadataColumns={that.props.metadataColumns} columnMetadata={that.props.columnMetadata} />
            });

            return (
                <table className={this.props.className}>
                    {title}
                    {nodes}
                </table>
            );
        }
    });

    var FComGridTitle = React.createClass({
        getDefaultProps: function(){
            return {
                "columns":[],
                "sortColumn": "",
                "sortAscending": true
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
            $(".dataTable th").resizable({handles: 'e'});
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
                                    <li><a href="#show_all">Show All</a></li>
                                    <li><a href="#show_sel">Show Selected</a></li>
                                    <li><a href="#upd_sel">Select Visible</a></li>
                                    <li><a href="#upd_unsel">Unselect Visible</a></li>
                                    <li><a href="#upd_clear">Unselect All</a></li>
                                </ul>
                            </div>
                        </th>
                    );
                }

                var columnSort = "";

                if (that.props.sortColumn == col && that.props.sortAscending){
                    columnSort = "sort-ascending th-sorting-asc"
                }  else if (that.props.sortColumn == col && that.props.sortAscending == false){
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





    //module.exports = FComGridBody;
    return FComGridBody;
});