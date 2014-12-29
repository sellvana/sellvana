/** @jsx React.DOM */

/**
 * FCom Row Component
 */
define(['underscore', 'react'], function (_, React) {
    /*
     var React = require('react/addons');
     var _ = require('underscore');
     */

    var FComRow = React.createClass({
        getDefaultProps: function () {
            return {
                "isChildRow": false,
                "showChildren": false,
                "data": {},
                "metadataColumns": [],
                "hasChildren": false,
                "columnMetadata": null,
                "columns": [],
                "selectable": true,
                "index": 0
            }
        },
        handleClick: function () {
            //this.props.toggleChildren();
        },
        render: function () {
            var that = this;

            var returnValue = null;

            var nodes = _.pairs(_.omit(this.props.data, this.props.metadataColumns)).map(function (col, index) {
                if (that.props.columnMetadata != null && that.props.columnMetadata.length > 0) {
                    var meta = _.findWhere(that.props.columnMetadata, {columnName: col[0]});
                    var colData = (typeof meta === 'undefined' || typeof meta.customComponent === 'undefined' || meta.customComponent === null) ? col[1] : <meta.customComponent data={col[1]} />;
                    returnValue = (meta == null ? returnValue : <td onClick={that.handleClick} className={meta.cssClassName}>{colData}</td>);
                }

                return returnValue || (<td onClick={that.handleClick}>{col[1]}</td>);
            });

            //this is kind of hokey - make it better
            var className = "standard-row";
            if (this.props.index % 2) {
                className += ' even';
            }

            if (that.props.isChildRow) {
                className = "child-row";
            } else if (that.props.hasChildren) {
                className = that.props.showChildren ? "parent-row expanded" : "parent-row";
            }
            var selectCol = this.props.selectable ? (<td data-col="0"><input type="checkbox" className="select-row" /></td>) : "";

            return (
                <tr className={className}>
                    {selectCol}
                    {nodes}
                </tr>
            );
        }
    });

    //module.exports = CustomRow;
    return FComRow;
})
