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

            var header = [];
            var columns = that.props.columns;
            for (var i=0; i<columns.length; i++) {
                if (columns[i] == "0") {
                    header.push(
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
                } else {
                    header.push(
                        <th className="js-draggable ui-resizable" data-id={columns[i]}>
                            <a className="js-change-url">{columns[i]}</a>
                            <div className="ui-resizable-handle ui-resizable-e"></div>
                        </th>
                    );
                }
            }

            var nodes = this.props.data.map(function (row, index) {
                return <FComRow data={row} index={index} columns={columns} metadataColumns={that.props.metadataColumns} columnMetadata={that.props.columnMetadata} />
            });

            return (
                <table className={this.props.className}>
                    <thead><tr>{header}</tr></thead>
                    {nodes}
                </table>
            );
        }
    });

    //module.exports = FComGridBody;
    return FComGridBody;
});