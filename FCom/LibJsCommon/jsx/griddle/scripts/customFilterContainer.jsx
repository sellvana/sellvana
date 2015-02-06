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

    var CustomFilterContainer = React.createClass({
        getDefaultProps: function () {
            return {
                "placeholderText": "",
                "customFilter": {}
            }
        },
        handleChange: function (event) {
            this.props.changeFilter(event.target.value);
        },
        render: function () {
            var that = this;

            if (typeof that.props.customFilter !== 'function') {
                console.log("Couldn't find valid template.");
                return (<div></div>);
            }

            return (<that.props.customFilter placeholderText={this.props.placeholderText} />);
        }
    });

    //module.exports = CustomFilterContainer;
    return CustomFilterContainer;
})
