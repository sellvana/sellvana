/**
 * Created by pp on 02-26-2015.
 */
define(['jquery', 'underscore', 'react', 'fcom.locale', 'daterangepicker'], function ($, _, React, Locale) {
    var PricesApp = React.createClass({
        render: function () {
            var childProps = _.omit(this.props, ['prices', 'deleted','validatePrices', 'title']);
            var baseFound = false;
            var priceOptions = {};
            _.each(this.props.price_types, function (op, k) {
                if(k !== 'promo') {
                    priceOptions[k] = op;
                }
            });
            var showFilters = this.props.show_customers || this.props.show_sites || this.props.show_currency;
            var colspan = 3 + (this.props.show_customers ? 1 : 0) + (this.props.show_sites ? 1 : 0) + (this.props.show_currency ? 1 : 0);

            return (
                <div id="prices">
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
                                <select id="filter_customer_group" ref="filter_customer_group"
                                        className="form-control">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.customer_groups, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            {this.props.show_sites ? <td>
                                <select id="filter_site" ref="filter_site" className="form-control">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.sites, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            {this.props.show_currency ? <td>
                                <select id="filter_currency" ref="filter_currency" className="form-control">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.currencies, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            <td></td>
                            <td colSpan="3"></td>
                        </tr>: null}

                        </thead>
                        <tbody>
                        {_.map(this.props['prices'], function (price) {
                            if (this.props['deleted'] && this.props['deleted'][price.id]) {
                                return <input key={'delete-' + price.id} type="hidden"
                                              name={"prices[delete][]"} value={price.id}/>
                            }

                            if (this.shouldPriceShow(price) === false) {
                                return <span key={'empty' + price.id}/>;
                            }

                            var theBase = false;
                            if(!baseFound) {
                                // if price type is base and site, currency and group are null, this is The base price?!
                                theBase = baseFound = (price['price_type'] == 'base') && (price['customer_group_id'] === null)
                                    && (price['site_id'] === null) && (price['currency_code'] === null);
                            }

                            return <PriceItem data={price} {...childProps} key={price['id']} priceOptions={priceOptions}
                                              validate={this.props.validatePrices} theBase={theBase}/>
                        }.bind(this))}
                        </tbody>
                        <tfoot>
                        <tr className="table-actions" style={{backgroundColor: "#ccc"}}>
                            <td></td>
                            <td>
                                <select id="price-types" className="form-control" ref="price-types">
                                <option value="-1">{Locale._("Add Price ...")}</option>
                                {_.map(priceOptions, function (pt, pk) {
                                    return <option key={pk} value={pk}
                                                   disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>
                                })}
                                </select>
                            </td>
                            <td colSpan={colspan}></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            );
        },
        shouldPriceShow: function (price) {
            if(price.variant_id) {
                return false;
            }
            var show = true;
            if (this.props['filter_customer_group_value'] && this.props['filter_customer_group_value'] !== '*' && this.props['filter_customer_group_value'] != price['customer_group_id']) {
                show = false;
            }
            if (this.props['filter_site_value'] && this.props['filter_site_value'] !== '*' && this.props['filter_site_value'] != price['site_id']) {
                show = false;
            }
            if (this.props['filter_currency_value'] && this.props['filter_currency_value'] !== '*' && this.props['filter_currency_value'] != price['currency_code']) {
                show = false;
            }
            return show;
        },
        componentDidUpdate: function () {
            //$('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
        },
        componentDidMount: function () {
            //$('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
            $(this.refs['price-types'].getDOMNode()).on("change", this.props.prices_add_new);
            _.each(['filter_customer_group', 'filter_site', 'filter_currency'], function (filter) {
                if (this.refs[filter]) {
                    var $filter = $(this.refs[filter].getDOMNode());
                    $filter.on('change', this.props.applyFilter)
                }
            }.bind(this));
            if(this.props.validatePrices) {
                this.props.validatePrices();
            }
        }
    });

    var PriceItem = React.createClass({
        editable: true,
        checkEditable: function (price) {
            var editable = true;
            if (editable) {
                editable = (this.props.editable_prices.indexOf(price['price_type']) != -1);
            }
            if (editable) {
                editable = !(this.props.theBase === true);
            }
            return editable;
        },
        render: function () {
            var price = this.props.data;
            this.editable = this.checkEditable(price);

            //if(this.editable) {
                    var priceTypes =
                    <span key="price_type_wrapper">
                        <select key="price_type" className={"form-control price-type" + (this.editable || this.props.theBase ? " priceUnique": '')}
                            name={this.getFieldName(price, 'price_type')} disabled={!this.editable}
                            defaultValue={price['price_type']} ref="price_type">
                                {_.map(this.props.price_types, function (pt, pk) {
                                    return <option key={pk} value={pk} disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>
                                })}
                        </select>
                        {!this.editable? <input type="hidden" value={price['price_type']} name={this.getFieldName(price, 'price_type')}/>: null}
                    </span>;

            //}

            var qty = <input key="qty_hidden" type="hidden" name={this.getFieldName(price, "qty")} defaultValue={price['qty']}/>;
            if (price['price_type'] === 'tier') {
                qty = <label key="qty_label">{Locale._("Qty")}<div style={{display: "inline-block", width:"30%", margin:"0 0 0 5px"}}><input key="qty" type="number" step="1"
                                                     className="form-control priceUnique"
                                                     name={this.getFieldName(price, "qty")}
                                                     placeholder={Locale._("Qty")}
                                                     defaultValue={price['qty']}
                                                     onChange={this.props.validate} size="2"
                                                     readOnly={this.editable ? null : 'readonly'}/></div></label>;
            }

            var dateRange = <span key="sale_period"/>;
            if(price['price_type'] === 'sale') {
                var dates = "";
                if(price['valid_from']) {
                    dates += price['valid_from'];
                    if(price['valid_to']) {
                        dates += this.props.sale_date_separator ? this.props.sale_date_separator : '/';
                        dates += price['valid_to']
                    }
                }
                dateRange = <input ref="sale_period" key="sale_period" type="text" className="form-control priceUnique"
                    name={this.getFieldName(price, "sale_period")} placeholder={Locale._("Select sale dates")}
                    defaultValue={dates} readOnly={this.editable ? null : 'readonly'}/>;
            }

            var operation = null, baseField = null;
            if(this.props.priceRelationOptions && this.props.priceRelationOptions[price['price_type']]) {
                var label = _.find(this.props.operationOptions, function (item) {
                    return price['operation'] == item['value'];
                });
                operation =
                        <select key="operation" name={this.getFieldName(price, 'operation')} defaultValue={price['operation']}
                            ref="operation" className="form-control" disabled={price['price_type'] == 'promo'}>
                            {this.props.operationOptions.map(function (o) {
                                return <option value={o.value} key={o.value}>{o.label}</option>
                            })}
                        </select>;
                if(price['operation'] && price['operation'] !== "=$") {
                    baseField =
                            <select ref="base_fields" key="base_fields" name={this.getFieldName(price, 'base_field')}
                                    defaultValue={price['base_field']} className="base_field form-control"
                                    onChange={this.props.validate}
                                    disabled={this.editable || this.props.theBase ? null: true}>
                                {this.props.priceRelationOptions[price['price_type']].map(function (p) {
                                    return <option key={p.value} value={p.value}>{p.label}</option>
                                })}
                            </select>
                }
            }

            var groups = null, sites = null, currencies = null;
            if(this.props.show_customers) {
                    groups =
                        <span key="cuatomer_groups">
                            <select name={this.getFieldName(price, "customer_group_id")}
                                    disabled={this.editable? null: true}
                                    defaultValue={price['customer_group_id']}
                                    className={"form-control customer-group" + (this.editable? " priceUnique": '')}>
                                <option value="*">{Locale._("Default")}</option>
                                {_.map(this.props.customer_groups, function (val, key) {
                                    return <option key={key} value={key}>{val}</option>
                                })}
                            </select>
                            {!this.editable ? <input type="hidden" name={this.getFieldName(price, "customer_group_id")}
                                                         value={price['customer_group_id']}/> : null}
                        </span>
            }
            if(this.props.show_sites) {
                    sites =
                    <span key="sites">
                        <select name={this.getFieldName(price, "site_id")} disabled={this.editable? null: true}
                                defaultValue={price['site_id']}
                                className={"form-control site" + (this.editable? " priceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.sites, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                            {!this.editable? <input type="hidden" name={this.getFieldName(price, "site_id")}
                           value={price['site_id']}/>: null}
                    </span>
            }
            if(this.props.show_currency) {
                    currencies =
                    <span>
                        <select name={this.getFieldName(price, "currency_code")} disabled={this.editable? null: true}
                                defaultValue={price['currency_code']}
                                className={"form-control currency" + (this.editable? " priceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.currencies, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                        {!this.editable? <input type="hidden" name={this.getFieldName(price, "currency_code")}
                           value={price['currency_code']}/>: null}
                    </span>
            }

            return (
                <tr className="price-item">
                    <td>
                        {this.editable? <a href="#" className="btn-remove" data-id={price.id}
                           id={"remove_price_btn_" + price.id}>
                            <span className="icon-remove-sign"></span>
                        </a>: null}
                        { price['product_id'] && price['product_id'] !== "*" ?
                            <input type="hidden" name={this.getFieldName(price, "product_id")}
                                   defaultValue={price['product_id']}/> : null }
                    </td>
                    { this.props.show_customers ? <td>{groups}</td>: null }
                    { this.props.show_sites ? <td>{sites}</td>: null }
                    { this.props.show_currency ? <td>{currencies}</td>: null }
                    <td>
                        {priceTypes}
                    </td>
                    <td>
                        <input type="text" className="form-control" name={this.getFieldName(price, "amount")} size="6" title={price['calc_amount']? price['calc_amount']: price['amount']}
                               defaultValue={price['amount']} readOnly={this.editable || this.props.theBase ? null: 'readonly'}/>
                        {price['calc_amount'] ? <span className="help-block">{price['calc_amount']}</span> : null}
                    </td>
                    <td>
                        { operation ? {operation} : null }
                        { baseField ? {baseField} : null }
                    </td>
                    <td>
                        {[qty, dateRange]}
                    </td>
                </tr>
            );
        },
        componentDidMount: function () {
            this.initPrices();
        },
        componentDidUpdate: function () {
            //if(this.props.data.operation && this.props.data.operation !== '=$') {
            //    $('select.base_field', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
            //}
            var operation = this.refs['operation'];
            if (operation) {
                var self = this;
                $(operation.getDOMNode()).off("change").on('change', function (e) {
                    var operation = $(e.target).val();
                    var id = self.props.data.id;

                    var baseField = null;
                    if (self.refs['base_fields']) {
                        baseField = $(self.refs['base_fields'].getDOMNode()).val();
                    }
                    self.props.updateOperation(id, operation, baseField);
                });
            }
            //this.initPrices();
        },
        componentWillUpdate: function () {
            //if(this.refs['base_fields']) {
            //    $(this.refs['base_fields'].getDOMNode()).select2('destroy');
            //}
        },
        initPrices: function () {
            var self = this;
            if (this.editable) {
                $(this.refs['price_type'].getDOMNode()).off("change").on('change', function (e) {
                    e.stopPropagation();
                    var priceType = $(e.target).val();
                    var id = self.props.data.id;
                    self.props.updatePriceType(id, priceType);
                    self.props.validate();
                });
                $('a.btn-remove', this.getDOMNode()).off("click").on('click', function (e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    //console.log(id);
                    self.props.deletePrice(id);
                });

                if(this.props.data['price_type'] === 'sale'){
                    this.initDateInput();
                }
            }

            if(this.editable || this.props.theBase) {
                var operation = this.refs['operation'];
                if (operation) {
                    $(operation.getDOMNode()).off("change").on('change', function (e) {
                        var operation = $(e.target).val();
                        var id = self.props.data.id;
                        var baseField = null;
                        if(self.refs['base_fields']) {
                            baseField = $(self.refs['base_fields'].getDOMNode()).val();
                        }
                        self.props.updateOperation(id, operation, baseField);
                    });
                }
            }
        },
        initDateInput: function () {
            var s = this.props.data['valid_from'], e = this.props.data['valid_to'];
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
        getFieldName: function (obj, field) {
            return "prices[productPrice][" + obj['id'] + "][" + field + "]";
        },
        _getPropOptionLabel: function (option, id) {
            if (null === id || undefined === id || false === id) {
                return Locale._("N/A");
            }
            if (this.props[option] && this.props[option][id]) {
                return this.props[option][id];
            }
            return id;
        },
        getCustomerGroupName: function (id) {
            return this._getPropOptionLabel('customer_groups', id);
        },
        getSiteName: function (id) {
            return this._getPropOptionLabel('sites', id);
        },
        getCurrencyName: function (id) {
            return this._getPropOptionLabel('currencies', id);
        }
    });

    var divStyle = {float: 'left', marginLeft: 15};
    function renderPrices(options, container) {
        var prices = options.prices;
        _.each(prices, function (price) {
            //console.log(price);
            if(price.operation && price.operation != '=$') {
                var operation = price.operation;
                var base_field = price.base_field;
                var customer_group_id = price.customer_group_id;
                var currency_code = price.currency_code;
                var site_id = price.site_id;
                var basePrice = _.find(prices, function (p) {
                    if(p === price) {
                        return false;
                    }

                    return (p.price_type == base_field && p.customer_group_id == customer_group_id &&
                    p.currency_code == currency_code && p.site_id == site_id)
                });
                //console.log(basePrice);
                if(basePrice) {
                    var result;
                    var value = parseFloat(basePrice.amount);
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
                    console.log(value, value2, operation, price.calc_amount);
                }
            }
        });
        React.render(<PricesApp {...options}/>, container);
    }

    var productPrice = {
        options: {
            title: Locale._("Product Prices")
        },
        newIdx: 0,
        init: function (options) {
            //var Perf = React.addons.Perf;
            this.options = _.extend({}, this.options, options);

            var container = this.options.container;
            if(!container || !container.length) {
                console.log("Prices div container not found");
                return;
            }
            var no_filters = true;

            var checkAddAllowed = function (options) {
                var allowed = null;
                _.each(['filter_customer_group_value', 'filter_site_value', 'filter_currency_value'], function (value) {
                    if(allowed) { // if any of the options allow it, then its allowed
                        return;
                    }
                    allowed = (options[value] != '*');
                });
            };
            this.options.applyFilter = function (e) {
                var $el = $(e.target);
                var filter = $el.attr('id');
                this.options[filter + '_value'] = $el.val();
                renderPrices(this.options, this.options.container[0]);
            }.bind(this);

            //checkAddAllowed(this.options);

            this.options.prices_add_new = function (e) {
                e.preventDefault();
                var type = $(e.target).val();
                $(e.target).val("-1");

                var newPrice = {
                    id: 'new_' + (this.newIdx++),
                    product_id: this.options.product_id,
                    price_type: type,
                    customer_group_id: this.options.filter_customer_group_value || null,
                    site_id: this.options.filter_site_value || null,
                    currency_code: this.options.filter_currency_value || null,
                    amount: null,
                    qty: 1
                };
                if(!this.options.prices) {
                    this.options.prices = [];
                }
                this.options.prices.push(newPrice);

                renderPrices(this.options, this.options.container[0]);
            }.bind(this);

            if(this.options.prices.length == 0) {
                var newPrice = {
                    id: 'new_' + (this.newIdx++),
                    product_id: this.options.product_id,
                    price_type: 'base',
                    customer_group_id: null,
                    site_id: null,
                    currency_code: null,
                    amount: null,
                    qty: 1
                };
                this.options.prices.push(newPrice);
            }


            this.options.deletePrice = function (id) {
                if (!this.options['deleted']) {
                    this.options['deleted'] = {};
                }
                this.options['deleted'][id] = true;
                renderPrices(this.options, this.options.container[0]);
            }.bind(this);

            this.options.updatePriceType = function (price_id, price_type) {
                _.each(this.options.prices, function (price) {
                    if (price.id == price_id) {
                        price.price_type = price_type;
                    }
                });
                //Perf.start();
                renderPrices(this.options, this.options.container[0]);
                //Perf.stop();
                //Perf.printInclusive();
            }.bind(this);

            this.options.updateOperation = function (price_id, operation, base_field) {
                _.each(this.options.prices, function (price) {
                    if (price.id == price_id) {
                        price.operation = operation;
                        price.base_field = base_field || 'base';
                    }
                });
                $("#price").find(".to-select2").select2('destroy');
                renderPrices(this.options, this.options.container[0]);
            }.bind(this);

            renderPrices(this.options, this.options.container[0]);
        }
    };
    return productPrice;
});
