/**
 * @jsx React.DOM
 *
 * FCom Select2 Component
 */
define(['underscore', 'react', 'fcom.components'], function (_, React, Components) {

    var FComSelect2 = React.createClass({
        displayName: "FComSelect2",
        getDefaultProps: function () {
            return {
                defaultValue: [],
                options: []
            };
        },
        getInitialState: function () {
            return {
                selections: []
            };
        },
        handleSelections: function (e, selections) {
            this.setState({ selections: selections });

            if (this.props.onChange) {
                this.props.onChange(e, this.props.callback, this.state.selections);
            }
        },
        componentDidUpdate: function() { 
        },
        shouldComponentUpdate: function(nextProps, nextState) {
            return nextState.selections !== this.state.selections;
        },
        render: function () {
            return (<Components.Select2 id={this.props.id} className={this.props.className} data-col={this.props['data-col']} name={this.props.name} options={this.props.options} onSelection={this.handleSelections} placeholder="Select some options" multiple={this.props.multiple || false} val={this.props.defaultValue} />);
        }
    });
    
    return FComSelect2;
});
