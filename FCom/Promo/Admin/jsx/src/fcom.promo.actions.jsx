/**
 * Created by pp on 22.Dec14.
 */

define(['react', 'jquery', 'jsx!fcom.components', 'jsx!fcom.promo.common', 'fcom.locale', 'select2'], function (React, $, Components, Common, Locale) {

    var Discount = React.createClass({
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <Type ref="discountType" id="discountType"> of </Type>
                    <div className="col-md-1">
                        <input className="form-control pull-left" ref="discountValue" id="discountValue" type="text" defaultValue={this.state.value}/>
                    </div>
                </Common.Row>
            );
        },
        getDefaultProps: function () {
            return {
                label: Locale._('Discount'),
                rowClass: 'discount'
            }
        },
        getInitialState: function () {
            return {value: 0};
        }
    });
    var Type = React.createClass({
        render: function () {
            var cls = this.props.select2 ? "to-select2 " : "";
            if (this.props.className) {
                cls += this.props.className;
            }
            return (
                <div className={this.props.containerClass}>
                    <select className={cls}>
                        {this.props.totalType.map(function (type) {
                            return <option value={type.id} key={type.id}>{type.label}</option>
                        })}
                    </select>
                    {this.props.children}
                </div>
            );
        },
        getDefaultProps: function () {
            return {
                totalType: [{id: "pcnt", label: "% Off"}, {id: "amt", label: "$ Amount"}],
                select2: true,
                containerClass: "col-md-2"
            };
        }
    });

    return React.createClass({
        render: function () {
            return (<Discount/>);
        }
    });
});
