define(['jquery', 'underscore', 'react', 'fcom.components', 'fcom.locale', 'daterangepicker'], function ($, _, React, Components, Locale) {
	var PriceItem = React.createClass({
        editable: true,
        componentDidMount: function() {
            var self = this;
            if (this.editable) {
                $(this.refs.price_type.getDOMNode()).off("change").on('change', function (e) {
                    e.stopPropagation();
                    var priceType = $(e.target).val();
                    var id = self.props.data.id;
                    self.props.updatePriceType(id, priceType);
                    self.props.validate();
                });
                if(this.props.data.price_type === 'sale') {
                    this.initDateInput();
                }
            }
        },
        initDateInput: function () {
            var s = this.props.data.valid_from, e = this.props.data.valid_to;
            var dateField = this.refs.sale_period;
            if (!s) {
                var startDate = new Date();
                s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            }
            if(!e) {
                e = s;
            }
            var $input = $(dateField.getDOMNode());
            var options = {
                "format": 'YYYY-MM-DD',
                "startDate": s,
                "opens": "left",
                "drops": "up",
                "buttonClasses": "btn btn-xs",
                "applyClass": "btn-success",
                "cancelClass": "btn-default",
                "showDropdowns": true,
                "separator": this.props.sale_date_separator? this.props.sale_date_separator :'/'
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
            return this.props.id + "Price[" + obj.id + "][" + field + "]";
        },
        updatePrice: function(e) {
            var $el = $(e.target);
            this.props.updatePriceField(this.props.data.id, $el.data('type'), $el.val());
        },
        updateOperation: function(e) {
            var operation = e.target.value;
            var id        = this.props.data.id;
            var baseField = null;
            if (this.refs.base_fields) {
                baseField = $(this.refs.base_fields.getDOMNode()).val();
            }
            this.props.updateOperation(id, operation, baseField);
            this.props.validate();
        },
        updatePriceType: function(e) {
            var priceType = e.target.value;
            var id        = this.props.data.id;
            this.props.updatePriceType(id, priceType);
            this.props.validate();
        },
        render: function() {
            var price = this.props.data;
            this.editable = this.checkEditable(price);
            var priceTypes =
                <span key="price_type_wrapper">
                    <select key="price_type" data-type='price_type' className={"form-control price-type " + (this.editable || this.props.theBase ? this.props.id + "PriceUnique": '')} name={this.getFieldName(price, 'price_type')} disabled={!this.editable} defaultValue={price.price_type} ref="price_type">
                            {_.map(this.props.price_types, function (pt, pk) {
                                return <option key={pk} value={pk} disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>;
                            })}
                    </select>
                    {!this.editable ? <input type="hidden" value={price.price_type} name={this.getFieldName(price, 'price_type')}/> : null}
                </span>;

            var qty = <input key="qty_hidden" data-type='qty' type="hidden" name={this.getFieldName(price, "qty")} defaultValue={price.qty}/>;
            if (price.price_type === 'tier') {
                qty = <label key="qty_label">{Locale._("Qty")}<div style={{display: "inline-block", width:"30%", margin:"0 0 0 5px"}}><input key="qty" data-type='qty' type="number" step="1" className={"form-control "+this.props.id+"PriceUnique"} name={this.getFieldName(price, "qty")} placeholder={Locale._("Qty")} defaultValue={price.qty} onChange={this.props.validate} size="2" readOnly={this.editable ? null : 'readonly'}/></div></label>;
            }

            var dateRange = <span key="sale_period"/>;
            if(price.price_type === 'sale') {
                var dates = "";
                if(price.valid_from) {
                    dates += price.valid_from;
                    if(price.valid_to) {
                        dates += this.props.sale_date_separator ? this.props.sale_date_separator : '/';
                        dates += price.valid_to;
                    }
                }
                dateRange = <input ref="sale_period" data-type='sale_period' key="sale_period" type="text" className={"form-control "+this.props.id+"PriceUnique"} name={this.getFieldName(price, "sale_period")} placeholder={Locale._("Select sale dates")} defaultValue={dates} readOnly={this.editable ? null : 'readonly'}/>;
            }

            var operation = null, baseField = null;
            if(this.props.priceRelationOptions && this.props.priceRelationOptions[price.price_type]) {
                var label = _.find(this.props.operationOptions, function (item) {
                    return price.operation == item.value;
                });
                operation =
                    <select key="operation" data-type='operation' name={this.getFieldName(price, 'operation')} defaultValue={price.operation}
                        ref="operation" className="form-control" disabled={price.price_type == 'promo'} onChange={this.updateOperation}>
                        {this.props.operationOptions.map(function (o) {
                            return <option value={o.value} key={o.value}>{o.label}</option>;
                        })}
                    </select>;
                if(price.operation && price.operation !== "=$") {
                    baseField =
                        <select ref="base_fields" data-type='base_field' key="base_field" name={this.getFieldName(price, 'base_field')} defaultValue={price.base_field} className="base_field form-control" onChange={this.updateOperation} disabled={this.editable || this.props.theBase ? null: true}>
                            {this.props.priceRelationOptions[price.price_type].map(function (p) {
                                return <option key={p.value} value={p.value}>{p.label}</option>;
                            })}
                        </select>;
                }
            }

            var groups = null, sites = null, currencies = null;
            if(this.props.show_customers) {
                groups =
                    <span key="cuatomer_groups">
                        <select data-type='customer_group_id' name={this.getFieldName(price, "customer_group_id")} disabled={this.editable? null: true} onChange={this.updatePrice} defaultValue={price.customer_group_id} data-type="customer_group_id" className={"form-control customer-group " + (this.editable ? this.props.id + "PriceUnique" : '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.customer_groups, function (val, key) {
                                return <option key={key} value={key}>{val}</option>;
                            })}
                        </select>
                        {!this.editable ? <input type="hidden" name={this.getFieldName(price, "customer_group_id")} value={price.customer_group_id}/> : null}
                    </span>;
            }
            if(this.props.show_sites) {
                sites =
                    <span key="sites">
                        <select data-type='site_id' name={this.getFieldName(price, "site_id")} disabled={this.editable? null: true} defaultValue={price.site_id} onChange={this.updatePrice} data-type="site_id" className={"form-control site " + (this.editable ? this.props.id + "PriceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.sites, function (val, key) {
                                return <option key={key} value={key}>{val}</option>;
                            })}
                        </select>
                        {!this.editable? <input type="hidden" name={this.getFieldName(price, "site_id")} value={price.site_id}/>: null}
                    </span>;
            }
            if(this.props.show_currency) {
                currencies =
                    <span key='currency_code'>
                        <select data-type='currency_code' name={this.getFieldName(price, "currency_code")} disabled={this.editable? null: true} defaultValue={price.currency_code} onChange={this.props.updatePrice} data-type="currency_code" className={"form-control currency " + (this.editable? this.props.id + "PriceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.currencies, function (val, key) {
                                return <option key={key} value={key}>{val}</option>;
                            })}
                        </select>
                        {!this.editable? <input type="hidden" name={this.getFieldName(price, "currency_code")} value={price.currency_code}/>: null}
                    </span>;
            }

            return (
                <tr className={this.props.id + "-price-item"} data-id={price.id}>
                    <td>
                        {this.editable ? <a href="javascript:void(0)" className="btn-remove" data-id={price.id} id={"remove_price_btn_" + price.id} onClick={this.props.deletePrice}> <span className="icon-remove-sign"></span></a> : null}
                        {this.props.variant_id ? <input type="hidden" defaultValue={this.props.variant_id} name={this.getFieldName(price, "variant_id")} /> : null}
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
            if (this.props.validatePrices) {
                this.props.validatePrices();
            }
        },
        shouldPriceShow: function (price) {
            if (this.props.id === 'product' && price.variant_id) {
                return false;
            }

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
            var self         = this;
            var childProps   = _.omit(this.props, ['prices', 'deleted','validatePrices', 'title']);
            var baseFound    = false;
            var priceOptions = {};
            _.each(this.props.price_types, function (op, k) {
                if(k !== 'promo') {
                    priceOptions[k] = op;
                }
            });
            var showFilters = this.props.show_customers || this.props.show_sites || this.props.show_currency;
            var colspan = 4 + (this.props.show_customers ? 1 : 0) + (this.props.show_sites ? 1 : 0) + (this.props.show_currency ? 1 : 0);
            return (
                <div id={this.props.id + "-prices"}>
                    <h4>{this.props.title.replace(/\-/g, ' ')}</h4>
                    <table className={"table table-striped "+this.props.id+"-prices-table"}>
                        <thead>
                        <tr className="table-title">
                            <th></th>
                            {this.props.show_customers? <th >{Locale._("Customer Group")}</th>: null}
                            {this.props.show_sites? <th >{Locale._("Site")}</th>: null}
                            {this.props.show_currency? <th >{Locale._("Currency")}</th>: null}
                            <th>{Locale._("Price Type")}</th>
                            <th>{Locale._("Amount")}</th>
                            <th>{Locale._("")}</th>
                            <th>{Locale._("")}</th>
                        </tr>
                        {showFilters? <tr className="table-actions" style={{backgroundColor: "#ccc"}}>
                            <td></td>
                            {this.props.show_customers ? <td>
                                <select id="filter_customer_group" ref="filter_customer_group" className="form-control" onChange={this.props.applyFilter}>
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.customer_groups, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>;
                                    })}
                                </select>
                            </td> : null}
                            {this.props.show_sites ? <td>
                                <select id="filter_site" ref="filter_site" className="form-control" onChange={this.props.applyFilter}>
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.sites, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>;
                                    })}
                                </select>
                            </td> : null}
                            {this.props.show_currency ? <td>
                                <select id="filter_currency" ref="filter_currency" className="form-control" onChange={this.props.applyFilter}>
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.currencies, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>;
                                    })}
                                </select>
                            </td> : null}
                            <td></td>
                            <td colSpan="4"></td>
                        </tr>: null}
                        </thead>
                        <tbody>
                            {_.map(this.props.prices, function (price) {
                                if (self.props.deleted && self.props.deleted[price.id] && !self.props.isLocal()) {
                                    return <input key={'delete-' + price.id} type="hidden" name={"prices[delete][]"} value={price.id}/>;
                                }

                                if (self.shouldPriceShow(price) === false) {
                                    return <span key={'empty' + price.id}/>;
                                }

                                var theBase = false;
                                if(!baseFound) {
                                    // if price type is base and site, currency and group are null, this is The base price?!
                                    theBase = baseFound = (price.price_type == 'base') && (price.customer_group_id === null) && (price.site_id === null) && (price.currency_code === null);
                                }

                                return <PriceItem data={price} id={self.props.id} {...childProps} key={price.id} priceOptions={priceOptions} theBase={theBase} updatePriceType={self.props.updatePriceType} updatePriceField={self.props.updatePriceField} updateOperation={self.props.updateOperation} deletePrice={self.props.deletePrice} validate={self.props.validatePrices}/>;
                            })}
                        </tbody>
                        <tfoot>
                            <tr className="table-actions" style={{backgroundColor: "#ccc"}}>
                                <td></td>
                                <td>
                                    <select id={"price-types-" + this.props.id} data-id={this.props.id} className="form-control" ref="price-types" onChange={this.props.addNewPrice.bind(null, this.props.add_price_type_callback)}>
                                    <option value="-1">{Locale._("Add Price ...")}</option>
                                    {_.map(priceOptions, function (pt, pk) {
                                        return <option key={pk} value={pk} disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>;
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

    var Price = React.createClass({
        displayName: "FComPrice",
        mixins: [FCom.Mixin],
        getInitialState: function() {
            return {
                options: {}
            };
        },
        getDefaultProps: function() {
            return  {
                id: 'price',
                options: {
                	id: 'product',
	                title: Locale._('Product Prices'),
	                data_mode: null,
	                prices: [],
	                deleted: [],
	                validatePrices: null,
                	sale_date_separator: ' / ',
                }
            };
        },
        addBlankPrice: function() {
            this.props.options.prices = [];
            var newPrice = {
                id: guid(),
                product_id: this.props.options.product_id,
                price_type: 'base',
                customer_group_id: null,
                site_id: null,
                currency_code: null,
                amount: null,
                qty: 1
            };
            this.props.options.prices.push(newPrice);
            this.setState({ options: this.props.options });
        },
        componentDidUpdate: function() {
            if (typeof this.props.options.prices === 'undefined' || this.props.options.prices.length === 0) {
                this.addBlankPrice();
            }
        },
        /**
         * Init prices data
         * 
         * @return mixed
         */
        componentDidMount: function() {
            if (typeof this.props.options.prices === 'undefined' || this.props.options.prices.length === 0) {
                this.addBlankPrice();

                if (typeof window[this.props.options.add_price_type_callback] === 'function') {
                    window[this.props.options.add_price_type_callback](this.props.options.prices, this.props.options.option);
                }
            }
        },
        isLocalMode: function() {
            return 'data_mode' in this.props.options && this.props.options.data_mode === 'local';
        },
        /**
         * Apply filter
         * 
         * @param  {Object} e 
         * @return mixed
         */
        applyFilter: function(e) {
            var $el = $(e.target);
            var filter = $el.attr('id');
            this.props.options[filter + '_value'] = $el.val();
            this.setState({ options: this.props.options });
        },
        /**
         * Add new blank price
         * 
         * @param {Object} e
         * @return mixed
         */
        addNewPrice: function(callback, e) {
            var type = $(e.target).val();
            var option = $(e.target).data('id');
            $(e.target).val("-1");

            var newPrice = {
                id: guid(),
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
            if (typeof window[callback] === 'function') {
                window[callback](this.props.options.prices, option);
            }
        },
        /**
         * Delete one price
         * 
         * @param  {Object} e 
         * @return mixed
         */
        deletePrice: function(e) {
            var id = $(e.target).parent().data('id');
            if (!this.props.options.deleted) {
                this.props.options.deleted = {};
            }
            this.props.options.deleted[id] = true;
            if (this.isLocalMode()) {
                var prices = this.props.options.prices;
                _.each(prices, function(price, index) {
                    if (price.id == id) {
                        this.props.options.prices.splice(index, 1);
                    }
                }.bind(this));
            }
            this.setState({ options: this.props.options });
        },
        /**
         * Update price type
         * 
         * @param  {integer} price_id
         * @param  {string} price_type 
         * @return mixed
         */
        updatePriceType: function(price_id, price_type) {
            _.each(this.props.options.prices, function (price) {
                if (price.id == price_id) {
                    price.price_type = price_type;
                    return;
                }
            });
            this.setState({ options: this.props.options });
        },
        /**
         * Update price operation
         * 
         * @param  {integer} price_id
         * @param  {string} operation 
         * @param  {string} base_field 
         * @return mixed
         */
        updateOperation: function(price_id, operation, base_field) {
            var options = this.props.options;
            _.each(this.props.options.prices, function (price) {
                if (price.id == price_id) {
                    price.operation = operation;
                    var defBaseField = options.priceRelationOptions[price.price_type];
                    if(defBaseField) {
                        defBaseField = defBaseField[0].value;
                    }
                    price.base_field = base_field || defBaseField;
                }
            });
            this.setState({ options: this.props.options });
        },
        /**
         * Update price field
         * 
         * @param  {integer} price_id 
         * @param  {string} field 
         * @param  {string} value
         * @return mixed
         */
        updatePriceField: function(price_id, field, value) {
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
        /**
         * Find base price for specific one
         * 
         * @param  {Object} price
         * @param  {Array} prices
         * @return {Object}
         */
        findBasePrice: function(price, prices) {
            var base_field        = price.base_field;
            var customer_group_id = price.customer_group_id;
            var currency_code     = price.currency_code;
            var site_id           = price.site_id;

            var possiblePrices = _.filter(prices, function (p) {
                return p.price_type == base_field;
            });

            if (possiblePrices.length === 0) {
                return;
            }

            var basePrice = _.find(possiblePrices, function (p) {
                return p.customer_group_id == customer_group_id &&
                    p.currency_code == currency_code &&
                    p.site_id == site_id;
            });
            if (!basePrice) {
                basePrice = _.find(possiblePrices, function (p) {
                    return (p.customer_group_id === null || p.customer_group_id === '') &&
                        p.currency_code == currency_code &&
                        p.site_id == site_id;
                });
            }
            if (!basePrice) {
                basePrice = _.find(possiblePrices, function (p) {
                    return p.customer_group_id == customer_group_id &&
                        (p.currency_code === null || p.currency_code === '') &&
                        p.site_id == site_id;
                });
            }
            if (!basePrice) {
                basePrice = _.find(possiblePrices, function (p) {
                    return p.customer_group_id == customer_group_id &&
                        p.currency_code == currency_code &&
                        (p.site_id === null || p.site_id === '');
                });
            }
            if (!basePrice) {
                basePrice = _.find(possiblePrices, function (p) {
                    return (p.customer_group_id === null || p.customer_group_id === '') &&
                        (p.currency_code === null || p.currency_code === '') &&
                        p.site_id == site_id;
                });
            }
            if (!basePrice) {
                basePrice = _.find(possiblePrices, function (p) {
                    return p.customer_group_id == customer_group_id &&
                        (p.currency_code === null || p.currency_code === '') &&
                        (p.site_id === null || p.site_id === '');
                });
            }
            if (!basePrice) {
                basePrice = _.find(possiblePrices, function (p) {
                    return (p.customer_group_id === null || p.customer_group_id === '') &&
                        p.currency_code == currency_code &&
                        (p.site_id === null || p.site_id === '');
                });
            }

            if (!basePrice) {
                basePrice = _.find(possiblePrices, function (p) {
                    return (p.customer_group_id === null || p.customer_group_id === '') &&
                        (p.currency_code === null || p.currency_code === '') &&
                        (p.site_id === null || p.site_id === '');
                });
            }

            return basePrice;
        },
        /**
         * Calculate price for specific one
         * 
         * @param  {Object} price  
         * @param  {Array} prices 
         * @return
         */
        collectPrice: function(price, prices) {
            var operation = price.operation;
            var basePrice = this.findBasePrice(price, prices);
            if (basePrice && basePrice != price) {
                if (basePrice.operation && basePrice.operation != '=$' && isNaN(basePrice.calc_amount)) {
                    collectPrice(basePrice, prices);
                }
                var result;
                var value  = parseFloat(basePrice.calc_amount || basePrice.amount);
                var value2 = parseFloat(price.amount);
                switch (operation) {
                    case '*$':
                        result = value * value2;
                        break;
                    case '+$':
                        result = value + value2;
                        break;
                    case '-$':
                        result = value - value2;
                        break;
                    case '*%':
                        result = value * value2 / 100;
                        break;
                    case '+%':
                        result = value + value * value2 / 100;
                        break;
                    case '-%':
                        result = value - value * value2 / 100;
                        break;
                    default:
                        result = value;
                }
                price.calc_amount = result;
            }
        },
        calculateDynamicPrice: function(options) {
            var self   = this;
            var prices = options.prices;
            _.each(prices, function (price) {
                price.calc_amount = null;
            });
            _.each(prices, function (price) {
                if (price.operation && price.operation != '=$') {
                    self.collectPrice(price, prices);
                }
            });
        },
        render: function() {
            this.calculateDynamicPrice(this.props.options);
            return (
                <PricesApp {...this.props.options} id={this.props.id} isLocal={this.isLocalMode} addNewPrice={this.addNewPrice} updatePriceType={this.updatePriceType} updatePriceField={this.updatePriceField} updateOperation={this.updateOperation} applyFilter={this.applyFilter} deletePrice={this.deletePrice} />
            );
        }
    });
    return Price;
});
