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
        mixins: [FCom.Mixin],
        getDefaultProps: function () {
            return {
                "row": {},
                "origRow": {},
                "index": 0,
                "columnMetadata": null,
                "doRowAction": null,
                "updateSelectedRow": null,
                "getSelectedRows": null
            }
        },
        selectRow: function(event) {
            this.props.updateSelectedRow(this.props.origRow, !event.target.checked);
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
                switch (col.type) {
                    case 'row_select':
                        node = <input type="checkbox" name={id + "[checked][" + that.props.row.id + "]"} className="select-row" onChange={that.selectRow} />;
                        break;
                    case 'btn_group':
                        var actions = col.buttons.map(function(btn) {
                            //var event = (typeof(btn.event) !== 'undefined') ? btn.event : '';
                            if (btn.type == 'link') {
                                return (
                                    <a className={"btn btn-link " + btn.cssClass} href={btn.href + that.props.row[btn.col]} title={btn.title ? btn.title : ""}>
                                        <i className={btn.icon}></i>
                                        {btn.caption}
                                    </a>
                                );
                            } else {
                                return (
                                    <button className={"btn btn-link " + btn.cssClass} title={btn.title ? btn.title : ""} type="button" onClick={that.props.doRowAction}>
                                        <i className={btn.icon} data-action={btn.name} data-row={that.props.row.id}></i>
                                        {btn.caption}
                                    </button>
                                );
                            }
                        });
                        node = (
                            <div className="table-actions-btns-group"> {actions} </div>
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
                        } else { //todo: add inline row
                            node = (typeof that.props.row[col.name] != 'undefined') ? that.props.row[col.name] : "";
                        }
                        break;
                    default:
                        if (col.display == 'eval') {
                            //use rc for compatibility old backbone grid
                            var rc = {
                                row: that.props.row
                            };
                            node = eval('print('+col.print+');');
                        } else if (col.display == 'file_size') {
                            node = that.fileSizeFormat(that.props.row[col.name]);
                        } else {
                            node = (typeof that.props.row[col.name] != 'undefined') ? that.props.row[col.name] : "";
                        }
                        break;
                }

                return <td data-col={col.name}>{node}</td>;
            });

            return (
                <tr className={"standard-row " + (this.props.index % 2 ? 'odd' : 'even')} id={this.props.row.id}>
                    {nodes}
                </tr>
            );
        }
    });

    //module.exports = CustomRow;
    return FComRow;
})
