/**
 * Created by pp on 02-26-2015.
 */
define(['jquery', 'underscore', 'react', 'fcom.locale', 'jquery.validate', 'daterangepicker'], function ($, _, React, Locale) {
    var PriceItem = React.createClass({
        editable: true,
        componentDidMount: function() {
            if(this.props.data.price_type === 'sale'){
                this.initDateInput();
            }
        },
        initDateInput: function () {
            var s = this.props.data.valid_from, e = this.props.data.valid_to;
            var dateField = this.refs['sale_period'];
            if (!s) {
                var startDate = new Date();
                s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            }
            if(!e) {
                e = s;
            }
            var $input = $(dateField.getDOMNode());
            var options = {
                format: 'YYYY-MM-DD',
                startDate: s,
                separator: this.props.sale_date_separator? this.props.sale_date_separator :'/'
            };
            if (e) {
                options.endDate = e;
            }
            $input.daterangepicker(options);
            //todo set setStartDate and setEndDate
        },
        checkEditable: function(price) {
            var editable = true;
            if (editable) {
                editable = this.props.editable_prices.indexOf(price.price_type) != -1;
            }
            if (editable) {
                editable = !(this.props.theBase === true);
            }
            return editable;
        },
        getFieldName: function(obj, field) {
            return "prices[productPrice][" + obj.id + "][" + field + "]";
        },
        updatePrice: function(e) {
            var $el = $(e.target);
            this.props.updatePriceField(this.props.data.id, $el.data('type'), $el.val());
        },
        updateOperation: function(e) {
            var operation = e.target.value;
            var id = this.props.data.id;
            var baseField = null;
            if (this.refs['base_fields']) {
                baseField = $(this.refs['base_fields'].getDOMNode()).val();
            }
            this.props.updateOperation(id, operation, baseField);
            this.props.validate();
        },
        updatePriceType: function(e) {
            var priceType = e.target.value;
            var id = this.props.data.id;
            this.props.updatePriceType(id, priceType);
            this.props.validate();
        },
        render: function () {
            var price = this.props.data;
            this.editable = this.checkEditable(price);

            var priceTypes =
                <span key="price_type_wrapper">
                    <select key="price_type" className={"form-control price-type" + (this.editable || this.props.theBase ? " priceUnique": '')} name={this.getFieldName(price, 'price_type')} disabled={!this.editable} defaultValue={price.price_type} ref="price_type" onChange={this.updatePriceType}>
                            {_.map(this.props.price_types, function (pt, pk) {
                                return <option key={pk} value={pk} disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>
                            })}
                    </select>
                    {!this.editable ? <input type="hidden" value={price.price_type} name={this.getFieldName(price, 'price_type')}/> : null}
                </span>;

            var qty = <input key="qty_hidden" type="hidden" name={this.getFieldName(price, "qty")} defaultValue={price.qty}/>;
            if (price.price_type === 'tier') {
                qty = <label key="qty_label">{Locale._("Qty")}<div style={{display: "inline-block", width:"30%", margin:"0 0 0 5px"}}><input key="qty" type="number" step="1" className="form-control priceUnique" name={this.getFieldName(price, "qty")} placeholder={Locale._("Qty")} defaultValue={price.qty} onChange={this.props.validate} size="2" readOnly={this.editable ? null : 'readonly'}/></div></label>;
            }

            var dateRange = <span key="sale_period"/>;
            if(price.price_type === 'sale') {
                var dates = "";
                if(price.valid_from) {
                    dates += price.valid_from;
                    if(price.valid_to) {
                        dates += this.props.sale_date_separator ? this.props.sale_date_separator : '/';
                        dates += price.valid_to
                    }
                }
                dateRange = <input ref="sale_period" key="sale_period" type="text" className="form-control priceUnique" name={this.getFieldName(price, "sale_period")} placeholder={Locale._("Select sale dates")} defaultValue={dates} readOnly={this.editable ? null : 'readonly'}/>;
            }

            var operation = null, baseField = null;
            if(this.props.priceRelationOptions && this.props.priceRelationOptions[price.price_type]) {
                var label = _.find(this.props.operationOptions, function (item) {
                    return price.operation == item.value;
                });
                operation =
                        <select key="operation" name={this.getFieldName(price, 'operation')} defaultValue={price.operation}
                            ref="operation" className="form-control" disabled={price.price_type == 'promo'} onChange={this.updateOperation}>
                            {this.props.operationOptions.map(function (o) {
                                return <option value={o.value} key={o.value}>{o.label}</option>
                            })}
                        </select>;
                if(price.operation && price.operation !== "=$") {
                    baseField =
                            <select ref="base_fields" key="base_fields" name={this.getFieldName(price, 'base_field')} defaultValue={price.base_field} className="base_field form-control" onChange={this.updateOperation} disabled={this.editable || this.props.theBase ? null: true}>
                                {this.props.priceRelationOptions[price.price_type].map(function (p) {
                                    return <option key={p.value} value={p.value}>{p.label}</option>
                                })}
                            </select>
                }
            }

            var groups = null, sites = null, currencies = null;
            if(this.props.show_customers) {
                groups =
                    <span key="cuatomer_groups">
                        <select name={this.getFieldName(price, "customer_group_id")} disabled={this.editable? null: true} onChange={this.updatePrice} defaultValue={price.customer_group_id} data-type="customer_group_id" className={"form-control customer-group" + (this.editable ? " priceUnique" : '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.customer_groups, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                        {!this.editable ? <input type="hidden" name={this.getFieldName(price, "customer_group_id")} value={price.customer_group_id}/> : null}
                    </span>
            }
            if(this.props.show_sites) {
                sites =
                    <span key="sites">
                        <select name={this.getFieldName(price, "site_id")} disabled={this.editable? null: true} defaultValue={price.site_id} onChange={this.updatePrice} data-type="site_id" className={"form-control site" + (this.editable ? " priceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.sites, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                        {!this.editable? <input type="hidden" name={this.getFieldName(price, "site_id")} value={price.site_id}/>: null}
                    </span>
            }
            if(this.props.show_currency) {
                currencies =
                    <span>
                        <select name={this.getFieldName(price, "currency_code")} disabled={this.editable? null: true} defaultValue={price.currency_code} onChange={this.props.updatePrice} data-type="currency_code" className={"form-control currency" + (this.editable? " priceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.currencies, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                        {!this.editable? <input type="hidden" name={this.getFieldName(price, "currency_code")} value={price.currency_code}/>: null}
                    </span>
            }

            return (
                <tr className="price-item">
                    <td>
                        {this.editable ? <a href="javascript:void(0)" className="btn-remove" data-id={price.id} id={"remove_price_btn_" + price.id} onClick={this.props.deletePrice}> <span className="icon-remove-sign"></span></a> : null}
                        { price.product_id && price.product_id !== "*" ? <input type="hidden" name={this.getFieldName(price, "product_id")} defaultValue={price.product_id}/> : null }
                    </td>
                    { this.props.show_customers ? <td>{groups}</td>: null }
                    { this.props.show_sites ? <td>{sites}</td>: null }
                    { this.props.show_currency ? <td>{currencies}</td>: null }
                    <td>{priceTypes}</td>
                    <td>
                        <input type="text" className="form-control" name={this.getFieldName(price, "amount")} size="6" onBlur={this.updatePrice} data-type="amount" defaultValue={price['amount']} readOnly={this.editable || this.props.theBase ? null: 'readonly'}/>
                    </td>
                    <td>
                        { operation ? {operation} : null }
                        { baseField ? {baseField} : null }
                    </td>
                    <td>
                        {[qty, dateRange]}
                    </td>
                    <td>
                        {price.calc_amount ? <span className="help-block">{price.calc_amount.toFixed(2)}</span> : null}
                    </td>
                </tr>
            );
        }
    });
    var PricesApp = React.createClass({
        componentDidMount: function () {
            if(this.props.validatePrices) {
                this.props.validatePrices();
            }
        },
        shouldPriceShow: function (price) {
            var show = true;
            if (this.props.filter_customer_group_value && this.props.filter_customer_group_value !== '*' && this.props.filter_customer_group_value != price.customer_group_id) {
                show = false;
            }
            if (this.props.filter_site_value && this.props.filter_site_value !== '*' && this.props.filter_site_value != price.site_id) {
                show = false;
            }
            if (this.props.filter_currency_value && this.props.filter_currency_value !== '*' && this.props.filter_currency_value != price.currency_code) {
                show = false;
            }
            return show;
        },
        render: function() {
            var self = this;
            var childProps = _.omit(this.props, ['prices', 'deleted','validatePrices', 'title']);
            var baseFound = false;
            var priceOptions = {};
            _.each(this.props.price_types, function (op, k) {
                if(k !== 'promo') {
                    priceOptions[k] = op;
                }
            });
            var showFilters = this.props.show_customers || this.props.show_sites || this.props.show_currency;
            var colspan = 4 + (this.props.show_customers ? 1 : 0) + (this.props.show_sites ? 1 : 0) + (this.props.show_currency ? 1 : 0);
            return (
                <div id="variant_prices">
                    <h4>{this.props.title}</h4>
                    <table className="table table-striped product-prices-table">
                        <thead>
                        <tr className="table-title">
                            <th ></th>
                            {this.props.show_customers? <th >{Locale._("Customer Group")}</th>: null}
                            {this.props.show_sites? <th >{Locale._("Site")}</th>: null}
                            {this.props.show_currency? <th >{Locale._("Currency")}</th>: null}
                            <th >{Locale._("Price Type")}</th>
                            <th >{Locale._("Amount")}</th>
                            <th >{Locale._("")}</th>
                            <th >{Locale._("")}</th>
                        </tr>
                        {showFilters? <tr className="table-actions" style={{backgroundColor: "#ccc"}}>
                            <td></td>
                            {this.props.show_customers ? <td>
                                <select id="filter_customer_group" ref="filter_customer_group" className="form-control" onChange={this.props.applyFilter}>
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.customer_groups, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            {this.props.show_sites ? <td>
                                <select id="filter_site" ref="filter_site" className="form-control" onChange={this.props.applyFilter}>
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.sites, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            {this.props.show_currency ? <td>
                                <select id="filter_currency" ref="filter_currency" className="form-control" onChange={this.props.applyFilter}>
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.currencies, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            <td></td>
                            <td colSpan="4"></td>
                        </tr>: null}
                        </thead>
                        <tbody>
                            {_.map(this.props.prices, function (price) {
                                if (self.props.deleted && self.props.deleted[price.id]) {
                                    return <input key={'delete-' + price.id} type="hidden" name={"prices[delete][]"} value={price.id}/>
                                }

                                if (self.shouldPriceShow(price) === false) {
                                    return <span key={'empty' + price.id}/>;
                                }

                                var theBase = false;
                                if(!baseFound) {
                                    // if price type is base and site, currency and group are null, this is The base price?!
                                    theBase = baseFound = (price.price_type == 'base') && (price.customer_group_id === null) && (price.site_id === null) && (price.currency_code === null);
                                }

                                return <PriceItem data={price} {...childProps} key={price.id} priceOptions={priceOptions} theBase={theBase} addNewPrice={self.props.addNewPrice} updatePriceType={self.props.updatePriceType} updatePriceField={self.props.updatePriceField} updateOperation={self.props.updateOperation} deletePrice={self.props.deletePrice} validate={self.props.validatePrices}/>
                            })}
                        </tbody>
                        <tfoot>
                            <tr className="table-actions" style={{backgroundColor: "#ccc"}}>
                                <td></td>
                                <td>
                                    <select id="price-types" className="form-control" ref="price-types" onChange={this.props.addNewPrice}>
                                    <option value="-1">{Locale._("Add Price ...")}</option>
                                    {_.map(priceOptions, function (pt, pk) {
                                        return <option key={pk} value={pk} disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>
                                    })}
                                    </select>
                                </td>
                                <td colSpan={colspan}></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            );
        }
    });

    var VariantPrice = React.createClass({
        displayName: "FComVariantPrice",
        newIdx: 0,
        getInitialState: function() {
            return {
                options: {}
            };
        },
        getDefaultProps: function() {
            return  {
                id: 'product',
                options: {}
            };
        },
        componentDidMount: function() {
            //
        },
        applyFilter: function(e) {console.log('applyFilter');
            var $el = $(e.target);
            var filter = $el.attr('id');
            this.props.options[filter + '_value'] = $el.val();
            this.setState({ options: this.props.options });
        },
        addNewPrice: function(e) {console.log('addNewPrice');
            var type = $(e.target).val();
            $(e.target).val("-1");

            var newPrice = {
                id: 'new_' + (this.newIdx++),
                product_id: this.props.options.product_id,
                price_type: type,
                customer_group_id: this.props.options.filter_customer_group_value || null,
                site_id: this.props.options.filter_site_value || null,
                currency_code: this.props.options.filter_currency_value || null,
                amount: null,
                qty: 1
            };
            if(!this.props.options.prices) {
                this.props.options.prices = [];
            }
            this.props.options.prices.push(newPrice);
            this.setState({ options: this.props.options });
        },
        deletePrice: function(e) {console.log('deletePrice');
            var id = $(e.target).parent().data('id');
            if (!this.props.options.deleted) {
                this.props.options.deleted = {};
            }
            this.props.options.deleted[id] = true;
            this.setState({ options: this.props.options });
        },
        updatePriceType: function(price_id, price_type) {console.log('updatePriceType');
            _.each(this.props.options.prices, function (price) {
                if (price.id == price_id) {
                    price.price_type = price_type;
                    return;
                }
            });
            this.setState({ options: this.props.options });
        },
        updateOperation: function(price_id, operation, base_field) {console.log('updateOperation');
            var options = this.props.options;
            _.each(this.props.options.prices, function (price) {
                if (price.id == price_id) {
                    price.operation = operation;
                    var defBaseField = options.priceRelationOptions[price.price_type];
                    if(defBaseField) {
                        defBaseField = defBaseField[0]['value'];
                    }
                    price.base_field = base_field || defBaseField;
                }
            });
            this.setState({ options: this.props.options });
        },
        updatePriceField: function(price_id, field, value) {console.log('updatePriceField');
            if(value === '*') {
                value = null;
            }
            _.each(this.props.options.prices, function (price) {
                if (price.id == price_id) {
                    price[field] = value;
                }
            });
            this.setState({ options: this.props.options });
        },
        render: function() {
            return (
                <PricesApp {...this.props.options} addNewPrice={this.addNewPrice} updatePriceType={this.updatePriceType} updatePriceField={this.updatePriceField} updateOperation={this.updateOperation} applyFilter={this.applyFilter} deletePrice={this.deletePrice} />
            );
        }
    });
    return VariantPrice;
});
