/** @jsx React.DOM */

/*
 Griddle - Simple Grid Component for React
 https://github.com/DynamicTyped/Griddle
 Copyright (c) 2014 Ryan Lanciaux | DynamicTyped

 See License / Disclaimer https://raw.githubusercontent.com/DynamicTyped/Griddle/master/LICENSE
 */
define(['react'], function (React) {
    /*
     var React = require('react/addons');
     */

    var CustomSettingsContainer = React.createClass({
        getDefaultProps: function () {
            return {
                "customSettings": {}
            }
        },
        handleChange: function (event) {

        },
        render: function () {
            var that = this;

            if (typeof that.props.customSettings !== 'function') {
                console.log("Couldn't find valid template.");
                return (<div></div>);
            }

            return (<that.props.customSettings selectedColumns={this.props.selectedColumns} setColumns={this.props.setColumns} />);
        }
    });

    //module.exports = CustomSettingsContainer;
    return CustomSettingsContainer;
})
