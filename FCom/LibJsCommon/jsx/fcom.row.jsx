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
        getDefaultProps: function () {
            return {
                "row": {},
                "columnMetadata": null,
                "index": 0,
                "doButtonAction": null
            }
        },
        fileSizeFormat: function (size) {
            var size = parseInt(size);
            if (size / (1024 * 1024) > 1) {
                size = size / (1024 * 1024);
                size = size.toFixed(2) + ' MB';
            } else if (size / 1024 > 1) {
                size = size / 1024;
                size = size.toFixed(2) + ' KB';
            } else {
                size = size + ' Byte';
            }

            return size;
        },
        validationRules: function(rules) {
            var str = '';
            for (var key in rules) {
                switch (key) {
                    case 'required':
                        str += 'data-rule-required="true" ';
                        break;
                    case 'email':
                        str += 'data-rule-email="true" ';
                        break;
                    case 'number':
                        str += 'data-rule-number="true" ';
                        break;
                    case 'digits':
                        str += 'data-rule-digits="true" ';
                        break;
                    case 'ip':
                        str += 'data-rule-ipv4="true" ';
                        break;
                    case 'url':
                        str += 'data-rule-url="true" ';
                        break;
                    case 'phoneus':
                        str += 'data-rule-phoneus="true" ';
                        break;
                    case 'minlength':
                        str += 'data-rule-minlength="' + rules[key] + '" ';
                        break;
                    case 'maxlength':
                        str += 'data-rule-maxlength="' + rules[key] + '" ';
                        break;
                    case 'max':
                        str += 'data-rule-max="' + rules[key] + '" ';
                        break;
                    case 'min':
                        str += 'data-rule-min="' + rules[key] + '" ';
                        break;
                    case 'range':
                        str += 'data-rule-range="[' + rules[key][0] + ',' + rules[key][1] + ']" ';
                        break;
                    case 'date':
                        str += 'data-rule-dateiso="true" data-mask="9999-99-99" placeholder="YYYY-MM-DD" ';
                        break;
                }
            }

            return str;
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
                        node = <input type="checkbox" name={id + "[checked][" + that.props.row.id + "]"} className="select-row" />;
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
                                    <button className={"btn btn-link " + btn.cssClass} title={btn.title ? btn.title : ""} type="button" onClick={that.props.doButtonAction}>
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
