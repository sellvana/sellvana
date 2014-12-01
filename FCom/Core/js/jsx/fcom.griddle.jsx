/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!griddle', 'backbone', 'underscore', 'bootstrap', 'jsx!griddle.customFormatContainer'], function(React, $, Griddle, Backbone, CustomFormatContainer) {
    FCom.Griddle = function(config) {
        var data = config.data.data;

        var FComGriddleComponent = React.createClass({
            render: function(){
                var content = <Griddle results={data}
                                tableClassName="fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable"
                                showFilter={true} showSettings={true} />;

                return (
                    <div>{content}</div>
                );
            }
        });

        React.render(
            <FComGriddleComponent />, document.getElementById(config.id)
        );

    };
});