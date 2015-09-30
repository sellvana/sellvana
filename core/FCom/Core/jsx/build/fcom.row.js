/** @jsx React.DOM */

/**
 * FCom Row Component
 */
define(['underscore', 'react', 'griddle.fcomSelect2'], function (_, React, FComSelect2) {
    /*
     var React = require('react/addons');
     var _ = require('underscore');
     */

    var FComRow = React.createClass({displayName: "FComRow",
        mixins: [FCom.Mixin, FCom.FormMixin],
        getDefaultProps: function () {
            return {
                "row": {},
                "index": 0,
                "columnMetadata": null,
                "doRowAction": null,
                "addSelectedRows": null,
                "getSelectedRows": null
            };
        },
        selectRow: function(event) {
            if (event.target.checked) {
                this.props.addSelectedRows([this.props.row]);
            } else {
                this.props.removeSelectedRows([this.props.row]);
            }
        },
        handleChange: function(callback, event) {
            var col = event.target.getAttribute('data-col');
            this.props.row[col] = event.target.value;

            if (typeof window[callback] === 'function') {
                return window[callback](event);
            }
        },
        handleSelect2Change: function(event, callback, selections) {
            var col = event.target.getAttribute('data-col');
            this.props.row[col] = event.target.value;

            if (typeof window[callback] === 'function') {
                return window[callback](event, selections);
            }
        },
        render: function () {
            var that = this;
            var id   = this.props.getConfig('id');
            var row  = that.props.row;

            var nodes = this.props.columns.map(function(column, index){
                var col = _.findWhere(that.props.columnMetadata, {name: column});
                if (!col) {
                    return React.createElement("td", null);
                }

                var node = "";
                var customNodeHtml = false;
                switch (col.type) {
                    case 'row_select':
                        var defaultChecked = false;
                        if (_.findWhere(that.props.getSelectedRows(), {id: row.id})) {
                            defaultChecked = true;
                        }
                        node = React.createElement("input", {type: "checkbox", name: id + "[checked][" + row.id + "]", className: "select-row", checked: defaultChecked, onChange: that.selectRow});
                        break;
                    case 'btn_group':
                        var actions = col.buttons.map(function(btn, index) {
                            //var event = (typeof(btn.event) !== 'undefined') ? btn.event : '';
                            if (btn.type === 'link') {
                                return (
                                    React.createElement("a", {key: index, 
                                        className: "btn btn-link " + (btn.cssClass ? btn.cssClass : ""), 
                                        title: btn.title ? btn.title : "", 
                                        href: btn.href + row[btn.col], 
                                        target: btn.target ? btn.target : ""
                                    }, 
                                        React.createElement("i", {className: btn.icon}), 
                                        btn.caption
                                    )
                                );
                            } else {
                                //todo: find another way to not use 2 times data-action and data-row in both <button> and <i> to make it is worked in Chrome + Firefox
                                return (
                                    React.createElement("button", React.__spread({className: "btn btn-link " + btn.cssClass, key: index, title: btn.title ? btn.title : "", type: "button", 
                                        "data-action": btn.name, "data-row": row.id},  btn.attrs, {onClick: that.props.doRowAction.bind(null, btn.callback)}), 
                                        React.createElement("i", {className: btn.icon, "data-action": btn.name, "data-row": row.id, "data-folder": row.folder ? row.folder : null}), 
                                        btn.caption
                                    )
                                );
                            }
                        });
                        node = (
                            React.createElement("div", {className: "table-actions-btns-group"}, actions)
                        );
                        break;
                    case 'input':
                        if (col.editable !== 'inline') {
                            switch (col.editor) {
                                case 'checkbox':
                                case 'radio':
                                    node = row[col.name] ? 'Yes' : 'No';
                                    break;
                                case 'select':
                                    node = col.options && col.options[row[col.name]] ? col.options[row[col.name]] : row[col.name];
                                    break;
                                default:
                                    node = (typeof row[col.name] !== 'undefined') ? row[col.name] : "";
                                    break;
                            }
                        } else { //inline mode

                            var validationRules = that.validationRules(col.validation);
                            
                            var defaultValue    = (typeof row[col.name] !== 'undefined') ? row[col.name] : "";
                            
                            var isSelect2       = col.select2 || false;

                            var inlineProps = {
                                id: id + '-' + col.name + '-' + row.id,
                                name: id + '[' + row.id + '][' + col.name + ']',
                                className: (col.cssClass ? col.cssClass : '') + ' form-control',
                                'data-col': col.name
                            };

                            if (typeof row[col.name + '_disabled'] !== 'undefined' && row[col.name + '_disabled'] == true) {
                                inlineProps.disabled = 'disabled';
                            }

                            switch (col.editor) {
                                case 'checkbox': //todo: need test again
                                case 'radio':
                                    node = React.createElement("input", React.__spread({key: col.name, type: "checkbox"},  inlineProps,  validationRules));
                                    break;
                                case 'textarea':  //todo: need test again
                                    node = React.createElement("textarea", React.__spread({key: col.name},  inlineProps,  validationRules, {rows: "4"}), row[col.name]);
                                    break;
                                case 'select':
                                    var selectOptions = [];
                                    if (_.isArray(col.options)) {
                                        selectOptions = col.options.map(function(opt, index) {
                                            if (isSelect2)
                                                return { id: index, text: opt };
                                            else return React.createElement("option", {key: index, value: opt.value});
                                        });
                                    } else {
                                        for(var key in col.options) {
                                            if (isSelect2)
                                                selectOptions.push({ id: key, text: col.options[key] });
                                            else
                                                selectOptions.push(React.createElement("option", {key: key, value: key}, col.options[key]));
                                        }
                                    }

                                    node = isSelect2 ? React.createElement(FComSelect2, React.__spread({},  inlineProps, {options: selectOptions, onChange: that.handleSelect2Change, defaultValue: isSelect2 ? [defaultValue] : defaultValue, callback: col.callback})) : React.createElement("select", React.__spread({key: col.name, defaultValue: defaultValue},  inlineProps,  validationRules, {onChange: that.handleChange.bind(null, col.callback)}), selectOptions);
                                    break;
                                default:
                                    node = React.createElement("input", React.__spread({key: col.name, type: "text"},  inlineProps,  col.attrs,  validationRules, {defaultValue: defaultValue, onChange: that.handleChange.bind(null, col.callback)}));
                                    break;
                            }
                            /*var inlineColValue = (typeof row[col.name] != 'undefined') ? row[col.name] : "";
                            node = (<input type="text" data-col={col.name} onChange={that.handleChange} defaultValue={inlineColValue} className="form-control js-draggable" name={id + "[" + row.id + "][" + col.name + "]"} />);*/
                        }
                        break;
                    case 'link':
                        var defaultValue = (typeof row[col.name] != 'undefined') ? row[col.name] : "";
                        var count = 0;
                        if (defaultValue) {
                            count = defaultValue.split(',').length;
                        }
                        var value = count + ' ' + col.value + (count <= 1 ? '' : 's');
                        
                        var inlineProps = {
                            href: col.href ? col.href : 'javascript:void(0)',
                            id: id + '-' + col.name + '-' + row.id,
                            name: id + '[' + row.id + '][' + col.name + ']',
                            className: (col.cssClass ? col.cssClass : ''),
                            style: (col.style ? col.style : ''),
                            "data-col": col.name,
                            'data-action': col.name,
                            'data-row': row.id,
                            'data-length': count,
                            defaultValue: defaultValue
                        };

                        node = React.createElement("a", React.__spread({key: col.name},  inlineProps, {onClick: col.action ? that.props.doRowAction.bind(null, col.action) : null}), value);
                        break;
                    default:
                        if (col.display == 'eval') {
                            //use rc for compatibility old backbone grid
                            var rc = {
                                row: row
                            };
                            node = eval(col.print);
                            customNodeHtml = true;
                        } else if (col.display == 'file_size') {
                            node = that.fileSizeFormat(row[col.name]);
                        } else {
                            node = (typeof row[col.name] != 'undefined') ? row[col.name] : "";
                            if (col.options && node !== '') {
                                node = col.options[node] || "";
                            }
                        }
                        break;
                }

                if (customNodeHtml) {
                    return React.createElement("td", {key: col.name, "data-col": col.name, dangerouslySetInnerHTML: {__html: node}});
                }
                return React.createElement("td", {"data-col": col.name, key: col.name}, node);
            });

            return (
                React.createElement("tr", {className: "standard-row " + (this.props.index % 2 ? 'odd' : 'even'), id: this.props.row.id, key: this.props.key}, 
                    nodes
                )
            );
        }
    });

    //module.exports = CustomRow;
    return FComRow;
})
