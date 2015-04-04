﻿/** @jsx React.DOM */

/*
   Griddle - Simple Grid Component for React
   https://github.com/DynamicTyped/Griddle
   Copyright (c) 2014 Ryan Lanciaux | DynamicTyped

   See License / Disclaimer https://raw.githubusercontent.com/DynamicTyped/Griddle/master/LICENSE
*/
define(['react'], function(React) {
/*
var React = require('react/addons');
*/
var GridNoData = React.createClass({displayName: "GridNoData",
    getDefaultProps: function(){
        return {
            "noDataMessage": "No Data"
        }
    },
    render: function(){
        var that = this;

        return(
            React.createElement("div", null, this.props.noDataMessage)
        );
    }
});

//module.exports = GridNoData;
return GridNoData;
})
