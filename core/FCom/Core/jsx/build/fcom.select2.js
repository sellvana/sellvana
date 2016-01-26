/**
 * @jsx React.DOM
 *
 * FCom Select2 Component
 */
define(['underscore', 'react', 'fcom.components', 'fcom.locale'], function (_, React, Components, Locale) {

    var FComSelect2 = React.createClass({
        displayName: "FComSelect2",
        getDefaultProps: function () {
            return {
                defaultValue: [],
                options: [],
                enabled: true
            };
        },
        getInitialState: function () {
            return {
                selections: []
            };
        },
        handleSelections: function (e, selections) {
            this.setState({selections: selections});

            if (this.props.onChange) {
                this.props.onChange(e, this.props.callback, this.state.selections);
            }
        },
        shouldComponentUpdate: function (nextProps, nextState) {
            return nextState.selections !== this.state.selections || nextProps.options !== this.props.options;
        },
        render: function () {
            return (React.createElement(Components.Select2, {id: this.props.id, 
                                        className: this.props.className, 
                                        attrs: this.props.attrs || {}, 
                                        "data-col": this.props['data-col'] || '', 
                                        name: this.props.name, 
                                        enabled: this.props.enabled, 
                                        options: this.props.options, 
                                        onSelection: this.handleSelections, 
                                        placeholder: this.props.placeholder || Locale._('Select some options'), 
                                        multiple: this.props.multiple || false, val: this.props.defaultValue})
            );
        }
    });

    return FComSelect2;
});
