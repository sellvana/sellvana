﻿/** @jsx React.DOM */

/*
 Griddle - Simple Grid Component for React
 https://github.com/DynamicTyped/Griddle
 Copyright (c) 2014 Ryan Lanciaux | DynamicTyped

 See License / Disclaimer https://raw.githubusercontent.com/DynamicTyped/Griddle/master/LICENSE
 */
define(['react', 'jsx!griddle.customRow'], function (React, CustomRow) {
    /*
     var React = require('react/addons');
     var GridRow = require('./gridRow.jsx');
     */

    var CustomRowContainer = React.createClass({
        getInitialState: function () {
            return {
                "data": {},
                "columns": [],
                "metadataColumns": []
            }
        },
        toggleChildren: function () {
            this.setState({
                showChildren: this.state.showChildren == false
            });
        },
        getInitialState: function () {
            return {showChildren: false};
        },
        render: function () {
            var that = this;

            if (typeof this.props.data === "undefined") {
                return (<tbody></tbody>);
            }
            var arr = [];
            var hasChildren = (typeof this.props.data["children"] !== "undefined") && this.props.data["children"].length > 0;

            arr.push(<CustomRow data={this.props.data} columns={that.props.columns} columnMetadata={this.props.columnMetadata} metadataColumns={that.props.metadataColumns} hasChildren={hasChildren} toggleChildren={that.toggleChildren} showChildren={that.state.showChildren}/>);

            if (that.state.showChildren) {
                var children = hasChildren && this.props.data["children"].map(function (row, index) {
                    if (typeof row["children"] !== "undefined") {
                        return (<tr>
                            <td colSpan={Object.keys(that.props.data).length - that.props.metadataColumns.length} className="griddle-parent">
                                <Griddle results={[row]} tableClassName="table" showTableHeading={false} showPager={false} columnMetadata={that.props.columnMetadata}/>
                            </td>
                        </tr>);
                    }

                    return <CustomRow data={row} columns={that.props.columns} metadataColumns={that.props.metadataColumns} isChildRow={true} columnMetadata={that.props.columnMetadata}/>
                });
            }

            return <tbody>{that.state.showChildren ? arr.concat(children) : arr}</tbody>
        }
    });

    //module.exports = CustomRowContainer;
    return CustomRowContainer;
})
