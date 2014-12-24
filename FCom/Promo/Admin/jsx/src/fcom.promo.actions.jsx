/**
 * Created by pp on 22.Dec14.
 */

define(['react', 'jquery', 'jsx!fcom.components', 'jsx!fcom.promo.common', 'fcom.locale', 'select2'], function (React, $, Components, Common, Locale) {
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

    var Discount = React.createClass({
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <Type ref="discountType" id="discountType" value={this.state.type}> of </Type>
                    <div className="col-md-1">
                        <input className="form-control pull-left" ref="discountValue" id="discountValue" type="text" defaultValue={this.state.value}/>
                        <select className="to-select2" ref="discountScope" id="discountScope">
                        {this.props.scopeOptions.map(function (type) {
                            return <option value={type.id} key={type.id}>{type.label}</option>
                        })}
                        </select>
                    </div>
                </Common.Row>
            );
        },
        componentDidMount: function () {
            $('select.to-select', this.getDOMNode()).select2().on('change', function (e) {
                var id = e.target.id;
                if(id == 'discountScope') {
                    this.setState({scope: e.val});
                }
            });
        },
        getDefaultProps: function () {
            return {
                label: Locale._('Discount'),
                rowClass: 'discount',
                scopeOptions: [
                    {id:'whole_order', label: 'Whole Order'},
                    {id:'cond_prod', label: 'Product from Conditions'},
                    {id:'other_prod', label: 'Other SKUs'},
                    {id:'attr_combination', label: 'Combination'}
                ]
            }
        },
        getInitialState: function () {
            return {
                value: 0,
                type:'',
                scope: 'order'
            };
        }
    });

    var FreeProduct = React.createClass({
        render: function () {
            return (
                <Common.Row >
                    <div className="col-md-3">
                        <input type="hidden" className="form-control" id="productSku" ref="productSku"/>
                    </div>
                    <div className="col-md-3">
                        <Components.Label input_id="productQty">{Locale._('Qty')}</Components.Label>
                        <input type="text" className="form-control" id="productQty" ref="productQty" defaultValue={this.state.qty}/>
                    </div>
                    <div className="col-md-3">
                        <Components.Label input_id="productTerms">{Locale._('Terms')}</Components.Label>
                        <input type="hidden" className="form-control" id="productTerms" ref="productTerms"/>
                    </div>
                </Common.Row>
            );
        },
        getInitialState: function () {
            return {
                skus: [],
                terms: [],
                qty: 0
            }
        }
    });

    var Shipping = React.createClass({
        render: function () {
            var amount = '';
            if(this.state.type != 'free') {
                amount = <input type="number" defaultValue={this.state.amount} id="shippingAmount" />
            }
            return (
                <Common.Row>
                    <Type ref="shippingType" id="shippingType"
                        totalType={[{id: "pcnt", label: "% Off"}, {id: "amt", label: "$ Amount Off"}, {id: "free", label:"Free"}]}/>
                    <div className="col-md-6">
                        {amount}
                        <Components.Label input_id="shippingMethods">{Locale._('For')}</Components.Label>
                        <input type="hidden" className="form-control" id="shippingMethods" ref="shippingMethods"/>
                    </div>
                </Common.Row>
            );
        },
        getInitialState: function () {
            return {
                type: 'free',
                methods: [],
                amount: 0
            }
        }
    });

    return React.createClass({
        render: function () {
            return (<div className="actions panel panel-default">
                    {this.state.data.map(function (field) {
                        //todo make a field based on field
                        var el;
                        var key = field.id;
                        switch(field.type){
                            case 'discount':
                                el = <Discount options={this.props.options} key={key} id={key} removeCondition={this.removeAction}
                                    modalContainer={this.props.modalContainer}/>;
                                break;
                            case 'free_products':
                                el = <FreeProduct options={this.props.options} key={key} id={key} removeCondition={this.removeAction}/>;
                                break;
                            case 'shipping':
                                el = <Shipping options={this.props.options} key={key} id={key} removeCondition={this.removeAction}/>;
                                break;

                        }
                        return el;
                    }, this)}
            </div> );
        },
        componentDidMount: function () {
            var $conditionsSerialized = $('#'+this.props.options.conditions_serialized);
            var data = this.state.data;

            if ($conditionsSerialized.length > 0) {
                try {
                    data = JSON.parse($conditionsSerialized.val());
                    this.setProps({data: data});
                    // todo actually update state
                } catch (e) {
                    console.log(e);
                }
            }

            $('#' + this.props.newAction).on('click', this.addAction);

            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15});
        },
        addAction: function () {
            // add condition data to state
            var $actionTypes = this.props.actionType;
            if($actionTypes.length == 0) {
                return;
            }

            var actionType = $actionTypes.val();
            var data = this.state.data;
            var condition = {type: actionType, id: actionType + '-' + this.state.lastActionId};
            data.push(condition);
            this.setState({data: data, lastActionId: (this.state.lastActionId + 1)});
        },
        removeAction: function (actionId) {
            var data = this.state.data;
            data = data.filter(function (field) {
                return field.id != actionId;
            });
            this.setState({data: data});
        },
        getInitialState: function () {
            return {
                data: [],
                lastActionId: 0
            };
        }
    });
});
