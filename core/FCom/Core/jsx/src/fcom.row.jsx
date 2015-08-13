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
        handleChange: function(event) {
            var col = event.target.getAttribute('data-col');
            this.props.row[col] = event.target.value;
        },
        render: function () {
            var that = this;
            var id = this.props.getConfig('id');
            var row = that.props.row;

            var nodes = this.props.columns.map(function(column, index){
                var col = _.findWhere(that.props.columnMetadata, {name: column});
                if (!col) {
                    return <td></td>;
                }

                var node = "";
                var customNodeHtml = false;
                switch (col.type) {
                    case 'row_select':
                        var defaultChecked = false;
                        if (_.findWhere(that.props.getSelectedRows(), {id: row.id})) {
                            defaultChecked = true;
                        }
                        node = <input type="checkbox" name={id + "[checked][" + row.id + "]"} className="select-row" checked={defaultChecked} onChange={that.selectRow} />;
                        break;
                    case 'btn_group':
                        var actions = col.buttons.map(function(btn, index) {
                            //var event = (typeof(btn.event) !== 'undefined') ? btn.event : '';
                            if (btn.type == 'link') {
                                return (
                                    <a key={index}
                                        className={"btn btn-link " + (btn.cssClass ? btn.cssClass : "")}
                                        title={btn.title ? btn.title : ""}
                                        href={btn.href + row[btn.col]}
                                        target={btn.target ? btn.target : ""}
                                    >
                                        <i className={btn.icon}></i>
                                        {btn.caption}
                                    </a>
                                );
                            } else {
                                //todo: find another way to not use 2 times data-action and data-row in both <button> and <i> to make it is worked in Chrome + Firefox
                                return (
                                    <button className={"btn btn-link " + btn.cssClass} key={index} title={btn.title ? btn.title : ""} type="button"
                                        data-action={btn.name} data-row={row.id} onClick={that.props.doRowAction.bind(null, btn.callback)}>
                                        <i className={btn.icon} data-action={btn.name} data-row={row.id}></i>
                                        {btn.caption}
                                    </button>
                                );
                            }
                        });
                        node = (
                            <div className="table-actions-btns-group">{actions}</div>
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
                                    node = (typeof row[col.name] != 'undefined') ? row[col.name] : "";
                                    break;
                            }
                        } else { //inline mode

                            var validationRules = that.validationRules(col.validation);
                            var inlineProps = {
                                id: id + '-' + col.name + '-' + row.id,
                                name: id + '[' + row.id + '][' + col.name + ']',
                                className: (col.cssClass ? col.cssClass : '') + ' form-control',
                                "data-col": col.name
                            };

                            var defaultValue = (typeof row[col.name] != 'undefined') ? row[col.name] : "";

                            if (typeof row[col.name + '_disabled'] !== 'undefined' && row[col.name + '_disabled'] == true) {
                                inlineProps.disabled = 'disabled';
                            }

                            switch (col.editor) {
                                case 'checkbox': //todo: need test again
                                case 'radio':
                                    node = <input key={col.name} type="checkbox" {...inlineProps} {...validationRules} />;
                                    break;
                                case 'textarea':  //todo: need test again
                                    node = <textarea key={col.name} {...inlineProps} {...validationRules} rows="4">{row[col.name]}</textarea>;
                                    break;
                                case 'select':
                                    var selectOptions = [];
                                    if (_.isArray(col.options)) {
                                        selectOptions = col.options.map(function(opt, index) {
                                            return <option key={index} value={opt.value}></option>;
                                        });
                                    } else {
                                        for(var key in col.options) {
                                            selectOptions.push(<option key={key} value={key}>{col.options[key]}</option>);
                                        }
                                    }
                                    
                                    node = (
                                        <select key={col.name} defaultValue={defaultValue} {...inlineProps} {...validationRules} onChange={that.handleChange}>{selectOptions}</select>
                                    );
                                    break;
                                default:
                                    node = <input key={col.name} type="text" {...inlineProps} {...validationRules} defaultValue={defaultValue} onChange={that.handleChange} />;
                                    break;
                            }
                            /*var inlineColValue = (typeof row[col.name] != 'undefined') ? row[col.name] : "";
                            node = (<input type="text" data-col={col.name} onChange={that.handleChange} defaultValue={inlineColValue} className="form-control js-draggable" name={id + "[" + row.id + "][" + col.name + "]"} />);*/
                        }
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
                    return <td key={col.name} data-col={col.name} dangerouslySetInnerHTML={{__html: node}}></td>;
                }
                return <td data-col={col.name} key={col.name}>{node}</td>;
            });

            return (
                <tr className={"standard-row " + (this.props.index % 2 ? 'odd' : 'even')} id={this.props.row.id} key={this.props.key}>
                    {nodes}
                </tr>
            );
        }
    });

    //module.exports = CustomRow;
    return FComRow;
})
