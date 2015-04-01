/** @jsx React.DOM */

/**
 * FCom Row Component
 */
define(['underscore', 'react'], function (_, React) {
    /*
     var React = require('react/addons');
     var _ = require('underscore');
     */

    var FComRow = React.createClass({displayName: "FComRow",
        mixins: [FCom.Mixin],
        getDefaultProps: function () {
            return {
                "row": {},
                "index": 0,
                "columnMetadata": null,
                "doRowAction": null,
                "addSelectedRows": null,
                "getSelectedRows": null
            }
        },
        selectRow: function(event) {
            if (event.target.checked) {
                this.props.addSelectedRows([this.props.row]);
            } else {
                this.props.removeSelectedRows([this.props.row]);
            }
        },
        render: function () {
            var that = this;
            var id = this.props.getConfig('id');

            //don't render if don't have id
            if (!this.props.row.id) {
                return null;
            }

            var nodes = this.props.columns.map(function(column, index){
                var col = _.findWhere(that.props.columnMetadata, {name: column});
                if (!col) {
                    return null;
                }

                var node = "";
                var customNodeHtml = false;
                switch (col.type) {
                    case 'row_select':
                        var defaultChecked = false;
                        if (_.findWhere(that.props.getSelectedRows(), {id: that.props.row.id})) {
                            defaultChecked = true;
                        }
                        node = React.createElement("input", {type: "checkbox", name: id + "[checked][" + that.props.row.id + "]", className: "select-row", checked: defaultChecked, onChange: that.selectRow});
                        break;
                    case 'btn_group':
                        var actions = col.buttons.map(function(btn, index) {
                            //var event = (typeof(btn.event) !== 'undefined') ? btn.event : '';
                            if (btn.type == 'link') {
                                return (
                                    React.createElement("a", {className: "btn btn-link " + (btn.cssClass || ''), key: index,
                                            href: btn.href + that.props.row[btn.col], title: btn.title || '', target: btn.target || ''},
                                        React.createElement("i", {className: btn.icon}), 
                                        btn.caption
                                    )
                                );
                            } else {
                                //todo: find another way to not use 2 times data-action and data-row in both <button> and <i> to make it is worked in Chrome + Firefox
                                return (
                                    React.createElement("button", {className: "btn btn-link " + (btn.cssClass || ''), key: index, title: btn.title || '', type: "button",
                                        "data-action": btn.name, "data-row": that.props.row.id, onClick: that.props.doRowAction},
                                        React.createElement("i", {className: btn.icon, "data-action": btn.name, "data-row": that.props.row.id}), 
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
                                    node = that.props.row[col.name] ? 'Yes' : 'No';
                                    break;
                                case 'select':
                                    node = col.options && col.options[that.props.row[col.name]] ? col.options[that.props.row[col.name]] : that.props.row[col.name];
                                    break;
                                default:
                                    node = (typeof that.props.row[col.name] != 'undefined') ? that.props.row[col.name] : "";
                                    break;
                            }
                        } else {
                            var inlineColValue = (typeof that.props.row[col.name] != 'undefined') ? that.props.row[col.name] : "";
                            node = (React.createElement("input", {type: "text", defaultValue: inlineColValue, className: "form-control js-draggable", name: id + "[" + col.name + "][" + that.props.row.id + "]"}));
                        }
                        break;
                    default:
                        if (col.display == 'eval') {
                            //use rc for compatibility old backbone grid
                            var rc = {
                                row: that.props.row
                            };
                            node = eval(col.print);
                            customNodeHtml = true;
                        } else if (col.display == 'file_size') {
                            node = that.fileSizeFormat(that.props.row[col.name]);
                        } else {
                            node = (typeof that.props.row[col.name] != 'undefined') ? that.props.row[col.name] : "";
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
