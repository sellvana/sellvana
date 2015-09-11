/**
 * Created by pp on 02-26-2015.
 *
 * Edited by pdtran on 08-28-2015
 *
 * Format code follow javasript's convention
 * Apply dynamic id, class and fix some functionality for common using
 */
define(['jquery', 'underscore', 'react', 'fcom.locale', 'daterangepicker'], function ($, _, React, Locale) {
    var PricesApp = React.createClass({
        componentDidUpdate: function () {
            //$('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
        },
        componentDidMount: function () {
            _.each(['filterCustomerGroup', 'filterSite', 'filterCurrency'], function (filter) {
                if (this.refs[filter]) {
                    var filterNode = $(this.refs[filter].getDOMNode());
                    filterNode.on('change', this.props.applyFilter);
                }
            }.bind(this));
            if(this.props.validatePrices) {
                this.props.validatePrices();
            }
        },
        shouldPriceShow: function (price) {
            // If render on price tab also price has variantId then return
            if (this.props.id == 'product' && price.variant_id) {
                return false;
            }

            var show = true;
            if (this.props.filterCustomerGroupValue && this.props.filterCustomerGroupValue !== '*' && this.props.filterCustomerGroupValue != price.customer_group_id) {
                show = false;
            }

            if (this.props.customerGroupId && this.props.customerGroupId !== '*' && this.props.customerGroupId != price.site_id) {
                show = false;
            }

            if (this.props.filterCurrencyValue && this.props.filterCurrencyValue !== '*' && this.props.filterCurrencyValue != price.currency_code) {
                show = false;
            }
            return show;
        },
        render: function () {
            var childProps   = _.omit(this.props, ['prices', 'deleted', 'validatePrices', 'title']);
            var baseFound    = false;
            var priceOptions = {};
            _.each(this.props.priceTypes, function (op, k) {
                if(k !== 'promo') {
                    priceOptions[k] = op;
                }
            });
            var showFilters = this.props.showCustomers || this.props.showSites || this.props.showCurrency;
            var colspan     = 4 + (this.props.showCustomers ? 1 : 0) + (this.props.showSites ? 1 : 0) + (this.props.showCurrency ? 1 : 0);
            // Apply dynamic id to avoid conflict between views
            return (
                <div id={this.props.id + "-prices"}>
                    <h4>{this.props.title.replace(/\-/g, ' ')}</h4>
                    <table className={"table table-striped " + this.props.id + "-prices-table"}>
                        <thead>
                        <tr className="table-title">
                            <th></th>
                            {this.props.showCustomers ? <th>{Locale._("Customer Group")}</th> : null}
                            {this.props.showSites ? <th>{Locale._("Site")}</th> : null}
                            {this.props.showCurrency ? <th>{Locale._("Currency")}</th> : null}
                            <th>{Locale._("Price Type")}</th>
                            <th>{Locale._("Amount")}</th>
                            <th>{Locale._("")}</th>
                            <th>{Locale._("")}</th>
                        </tr>
                        {showFilters ? <tr className="table-actions" style={{backgroundColor: "#ccc"}}>
                            <td></td>
                            {this.props.showCustomers ? <td>
                                <select id="filter_customer_group" ref="filterCustomerGroup" className="form-control">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.customerGroups, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            {this.props.showSites ? <td>
                                <select id="filter_site" ref="filterSite" className="form-control">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.sites, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td> : null}
                            {this.props.showCurrency ? <td>
                                <select id="filter_currency" ref="filterCurrency" className="form-control">
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
                                if (this.props.deleted && _.contains(this.props.deleted, parseInt(price.id))) {
                                    return <input key={'delete-' + price.id} type="hidden" name={"prices[delete][]"} value={price.id}/>
                                }

                                /**
                                 * Return if shouldPriceShow return false
                                 * 
                                 * to avoid DOM error on re-render when delete price on prices tab ( if variant has prices )
                                 */
                                if (this.shouldPriceShow(price) === false) {
                                    return;
                                }

                                var theBase = false;
                                if(!baseFound) {
                                    // if price type is base and site, currency and group are null, this is The base price?!
                                    theBase = baseFound = (price.price_type == 'base') && (price.customer_group_id === null)
                                        && (price.site_id === null) && (price.currency_code === null);
                                }

                                return <PriceItem data={price} {...childProps} key={price.id} priceOptions={priceOptions} validate={this.props.validatePrices} theBase={theBase}/>
                            }.bind(this))}
                        </tbody>
                        <tfoot>
                        <tr className="table-actions" style={{backgroundColor: "#ccc"}}>
                            <td></td>
                            <td>
                                <select id={"price-types-" + this.props.id} data-id={this.props.id} className="form-control" ref="price-types" onChange={this.props.addNewPrice.bind(null, this.props.addPriceCallback)}>
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

    var PriceItem = React.createClass({
        editable: true,
        checkEditable: function (price) {
            var editable = true;
            if (editable) {
                editable = (this.props.editablePrices.indexOf(price.price_type) != -1);
            }
            if (editable) {
                editable = !(this.props.theBase === true);
            }
            return editable;
        },
        componentDidMount: function () {
            this.initPrices();
        },
        componentDidUpdate: function () {
            if (this.props.data.price_type === 'sale') {
                this.initDateInput();
            }
        },
        updatePrice: function (e) {
            var el = $(e.target);
            this.props.updatePriceField(this.props.data.id, el.data('type'), el.val());
        },
        updateOperation: function (e) {
            var operation = e.target.value;
            var id        = this.props.data.id;
            var baseField = null;
            if (this.refs.baseFields) {
                baseField = $(this.refs.baseFields.getDOMNode()).val();
            }
            this.props.updateOperation(id, operation, baseField);
            this.props.validate();
        },
        initPrices: function () {
            var self = this;
            if (this.editable) {
                $(this.refs.priceType.getDOMNode()).off("change").on('change', function (e) {
                    e.stopPropagation();
                    var priceType = $(e.target).val();
                    var id = self.props.data.id;
                    self.props.updatePriceType(id, priceType);
                    self.props.validate();
                });
                $('a.btn-remove', this.getDOMNode()).off("click").on('click', function (e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    self.props.deletePrice(id);
                });

                if(this.props.data.price_type === 'sale'){
                    this.initDateInput();
                }
            }
        },
        initDateInput: function () {
            if (!this.props.data.valid_from) {
                this.props.data['valid_from'] = null;
            }

            if (!this.props.data.valid_to) {
                this.props.data['valid_to'] = null;
            }

            var s = this.props.data.valid_from, e = this.props.data.valid_to;
            var dateField = this.refs.salePeriod;
            if (!s) {
                var startDate = new Date();
                s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            }
            if(!e) {
                e = s;
            }
            var input = $(dateField.getDOMNode());
            var options = {
                "format": 'YYYY-MM-DD',
                "startDate": s,
                "opens": "left",
                "drops": "up",
                "buttonClasses": "btn btn-xs",
                "applyClass": "btn-success",
                "cancelClass": "btn-default",
                "showDropdowns": true,
                "separator": this.props.saleDateSeparator ? this.props.saleDateSeparator : ' / '
            };
            if (e) {
                options.endDate = e;
            }
            input.daterangepicker(options);

            input.on('apply.daterangepicker', function(ev, picker) {
                var dates = input.val();

                if (dates && dates.split(options.separator).length) {
                    this.props.data.valid_from = dates.split(options.separator)[0];
                    $(this.refs.validFrom.getDOMNode()).val(this.props.data.valid_from);

                    this.props.data.valid_to = dates.split(options.separator)[1];
                    $(this.refs.validTo.getDOMNode()).val(this.props.data.valid_to);
                }

            }.bind(this));
            //todo set setStartDate and setEndDate
        },
        getFieldName: function (obj, field) {
            // Apply dynamic name depend on id for executing data on server side
            return this.props.id + "Price[" + obj.id + "][" + field + "]";
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
            return this._getPropOptionLabel('customerGroups', id);
        },
        getSiteName: function (id) {
            return this._getPropOptionLabel('sites', id);
        },
        getCurrencyName: function (id) {
            return this._getPropOptionLabel('currencies', id);
        },
        render: function () {
            var price = this.props.data;
            this.editable = this.checkEditable(price);

            // Apply dynamic class for unique validation depend on id
            var priceTypes =
                <span key="priceTypeWrapper">
                    <select key="priceType" data-type='price_type' className={"form-control price-type " + (this.editable || this.props.theBase ? this.props.id + "PriceUnique": '')} name={this.getFieldName(price, 'price_type')} disabled={!this.editable} defaultValue={price.price_type} ref="priceType">
                            {_.map(this.props.priceTypes, function (pt, pk) {
                                return <option key={pk} value={pk} disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>
                            })}
                    </select>
                    {!this.editable? <input type="hidden" value={price.price_type} name={this.getFieldName(price, 'price_type')}/>: null}
                </span>;

            var qty = <input key="qtyHidden" data-type='qty' type="hidden" name={this.getFieldName(price, "qty")} defaultValue={price.qty}/>;
            if (price.price_type === 'tier') {
                qty = <label key="qty_label">{Locale._("Qty")}
                            <div style={{display: "inline-block", width:"30%", margin:"0 0 0 5px"}}>
                                <input key="qty" data-type='qty' type="number" step="1" className={"form-control " + this.props.id + "PriceUnique"} 
                                        name={this.getFieldName(price, "qty")} placeholder={Locale._("Qty")} defaultValue={price.qty} 
                                        onChange={this.props.validate} size="2" readOnly={this.editable ? null : 'readonly'}/>
                            </div>
                      </label>;
            }

            var dateRange = <span key="salePeriod"/>;
            var validFrom = validTo = null;
            if(price.price_type === 'sale') {
                var dates = "";
                if(price.valid_from) {
                    dates += price.valid_from;
                    if(price.valid_to) {
                        dates += this.props.saleDateSeparator ? this.props.saleDateSeparator : ' / ';
                        dates += price.valid_to;
                    }
                }
                dateRange = <input ref="salePeriod" data-type='sale_period' key="salePeriod" type="text" 
                                className={"form-control " + this.props.id + "PriceUnique"} 
                                name={this.getFieldName(price, "sale_period")} placeholder={Locale._("Select sale dates")} 
                                defaultValue={dates} readOnly={this.editable ? null : 'readonly'}/>;

                validFrom = <input ref="validFrom" data-type='valid_from' key='validFrom' type='hidden' defaultValue={price.valid_from} />;
                validTo = <input ref="validTo" data-type='valid_to' key='validTo' type='hidden' defaultValue={price.valid_to} />;
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
                                return <option value={o.value} key={o.value}>{o.label}</option>
                            })}
                        </select>;
                if(price.operation && price.operation !== "=$") {
                    baseField =
                            <select ref="baseFields" data-type='base_field' key="baseFields" name={this.getFieldName(price, 'base_field')}
                                    defaultValue={price.base_field} className={this.props.id + "BaseField form-control"}
                                    onChange={this.updateOperation}
                                    disabled={this.editable || this.props.theBase ? null : true}>
                                {this.props.priceRelationOptions[price.price_type].map(function (p) {
                                    return <option key={p.value} value={p.value}>{p.label}</option>
                                })}
                            </select>
                }
            }

            var groups = null, sites = null, currencies = null;
            if(this.props.showCustomers) {
                groups =
                    <span key="cuatomer_groups">
                        <select name={this.getFieldName(price, "customer_group_id")}
                                disabled={this.editable? null: true} onChange={this.updatePrice}
                                defaultValue={price.customer_group_id} data-type="customer_group_id"
                                className={"form-control customer-group " + (this.editable ? this.props.id + "PriceUnique" : '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.customerGroups, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                        {!this.editable ? <input type="hidden" name={this.getFieldName(price, "customer_group_id")} value={price.customer_group_id}/> : null}
                    </span>
            }
            if(this.props.showSites) {
                    sites =
                    <span key="sites">
                        <select name={this.getFieldName(price, "site_id")} disabled={this.editable ? null : true}
                                defaultValue={price.site_id} onChange={this.updatePrice} data-type="site_id"
                                className={"form-control site " + (this.editable ? this.props.id + "PriceUnique" : '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.sites, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                            {!this.editable ? <input type="hidden" name={this.getFieldName(price, "site_id")} value={price.site_id}/> : null}
                    </span>
            }
            if(this.props.showCurrency) {
                    currencies =
                    <span key='currencyCode'>
                        <select name={this.getFieldName(price, "currency_code")} disabled={this.editable ? null : true}
                                defaultValue={price.currency_code} onChange={this.updatePrice} data-type="currency_code"
                                className={"form-control currency " + (this.editable ? this.props.id + "PriceUnique" : '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.currencies, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                        {!this.editable ? <input type="hidden" name={this.getFieldName(price, "currency_code")} value={price.currency_code}/> : null}
                    </span>
            }

            return (
                <tr className={this.props.id + "-price-item"} data-id={price.id}>
                    <td>
                        { this.editable ? <a href="#" className="btn-remove" data-id={price.id} id={"remove_price_btn_" + price.id}> <span className="icon-remove-sign"></span></a> : null }
                        { this.props.variantId ? <input type="hidden" defaultValue={this.props.variantId} name={this.getFieldName(price, "variant_id")} /> : null }
                        { price.product_id && price.product_id !== "*" ? <input type="hidden" name={this.getFieldName(price, "product_id")} defaultValue={price.product_id}/> : null }
                    </td>
                    { this.props.showCustomers ? <td>{groups}</td> : null }
                    { this.props.showSites ? <td>{sites}</td> : null }
                    { this.props.showCurrency ? <td>{currencies}</td> : null }
                    <td>
                        {priceTypes}
                    </td>
                    <td>
                        <input type="text" className="form-control" name={this.getFieldName(price, "amount")} size="6" onBlur={this.updatePrice} data-type="amount"
                               defaultValue={price.amount} readOnly={this.editable || this.props.theBase ? null : 'readonly'}/>
                    </td>
                    <td>
                        { operation ? {operation} : null }
                        { baseField ? {baseField} : null }
                    </td>
                    <td>
                        {[qty, dateRange, validFrom, validTo]}
                    </td>
                    <td>
                        { price.calc_amount ? <span className="help-block">{price.calc_amount.toFixed(2)}</span> : null }
                    </td>
                </tr>
            );
        }
    });

    function findBasePrice(price, prices) {
        var baseField       = price.base_field;
        var customerGroupId = price.customer_group_id;
        var currencyCode    = price.currency_code;
        var siteId          = price.site_id;

        var possiblePrices = _.filter(prices, function (p) {
            return p.price_type == baseField;
        });

        if (possiblePrices.length == 0) {
            return;
        }

        var basePrice = _.find(possiblePrices, function (p) {
            return p.customer_group_id == customerGroupId &&
                p.currency_code == currencyCode &&
                p.site_id == siteId;
        });

        if (!basePrice) {
            basePrice = _.find(possiblePrices, function (p) {
                return (p.customer_group_id == null || p.customer_group_id == '') &&
                    p.currency_code == currencyCode &&
                    p.site_id == siteId;
            });
        }

        if (!basePrice) {
            basePrice = _.find(possiblePrices, function (p) {
                return p.customer_group_id == customerGroupId &&
                    (p.currency_code == null || p.currency_code == '') &&
                    p.site_id == siteId;
            });
        }

        if (!basePrice) {
            basePrice = _.find(possiblePrices, function (p) {
                return p.customer_group_id == customerGroupId &&
                    p.currency_code == currencyCode &&
                    (p.site_id == null || p.site_id == '');
            });
        }

        if (!basePrice) {
            basePrice = _.find(possiblePrices, function (p) {
                return (p.customer_group_id == null || p.customer_group_id == '') &&
                    (p.currency_code == null || p.currency_code == '') &&
                    p.site_id == siteId;
            });
        }

        if (!basePrice) {
            basePrice = _.find(possiblePrices, function (p) {
                return p.customer_group_id == customerGroupId &&
                    (p.currency_code == null || p.currency_code == '') &&
                    (p.site_id == null || p.site_id == '');
            });
        }

        if (!basePrice) {
            basePrice = _.find(possiblePrices, function (p) {
                return (p.customer_group_id == null || p.customer_group_id == '') &&
                    p.currency_code == currencyCode &&
                    (p.site_id == null || p.site_id == '');
            });
        }

        if (!basePrice) {
            basePrice = _.find(possiblePrices, function (p) {
                return (p.customer_group_id == null || p.customer_group_id == '') &&
                    (p.currency_code == null || p.currency_code == '') &&
                    (p.site_id == null || p.site_id == '');
            });
        }

        return basePrice;
    }

    function collectPrice(price, prices) {
        var operation = price.operation;
        var basePrice = findBasePrice(price, prices);
        if (basePrice && basePrice != price) {
            if (basePrice.operation && basePrice.operation != '=$' && isNaN(basePrice.calc_amount)) {
                collectPrice(basePrice, prices);
            }
            var result;
            var value = parseFloat(basePrice.calc_amount || basePrice.amount);
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
    }

    function calculateDynamicPrice(options) {
        var prices = options.prices;
        _.each(prices, function (price) {
            price.calc_amount = null;
        });
        _.each(prices, function (price) {
            if (price.operation && price.operation != '=$') {
                collectPrice(price, prices);
            }
        });
    }

    var Price = React.createClass({
        displayName: "FComPrice",
        mixins: [FCom.Mixin],
        getDefaultPriceTypes: function() {
            return {
                base: "Base Price",
                cost: "Cost",
                map: "MAP",
                msrp: "MSRP",
                promo: "Promo Price",
                sale: "Sale Price",
                tier: "Tier Price"
            };
        },
        getDefaultEditablePrices: function() {
            return ["base", "map", "msrp", "sale", "tier", "cost"];
        },
        getUrlParamByName: function(name){
            if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
                return decodeURIComponent(name[1]);
        },
        getDefaultProps: function() {
            return  {
                title: Locale._('Prices'),
                productId: '',
                prices: [],
                priceRelationOptions: {},
                operationOptions: [],
                priceTypes: this.getDefaultPriceTypes,
                editablePrices: this.getDefaultEditablePrices,
                customerGroups: null,
                sites: null,
                deleted: [],
                showCustomers: false,
                showSites: false,
                showCurrency: false,
                saleDateSeparator: ' / '
            };
        },
        getInitialState: function() {
            return _.extend({}, this.props, this.props.options);
        },
        init: function() {
            this.state.applyFilter = function (e) {
                var el = $(e.target);
                var filter = el.attr('id');
                this.state[filter + '_value'] = el.val();
                this.forceUpdate();
            }.bind(this);

            this.state.addNewPrice = function (callback, e) {
                var type = $(e.target).val();
                var option = $(e.target).data('id');
                $(e.target).val("-1");

                var newPrice = {
                    id: guid(),
                    product_id: this.state.productId,
                    price_type: type,
                    customer_group_id: this.state.filterCustomerGroupValue || null,
                    site_id: this.state.filterSiteValue || null,
                    currency_code: this.state.filterCurrencyValue || null,
                    amount: null,
                    qty: 1
                };

                this.state.prices.push(newPrice);
                this.forceUpdate();

                if (typeof window[callback] === 'function') {
                    window[callback](this.state.prices, option);
                }
            }.bind(this);

            this.state.deletePrice = function (id) {
                this.state.deleted.push(id);
                this.forceUpdate();
            }.bind(this);

            this.state.updatePriceType = function (priceId, priceType) {
                _.each(this.state.prices, function (price) {
                    if (price.id == priceId) {
                        price.price_type = priceType;
                    }
                });
                this.forceUpdate();
            }.bind(this);

            this.state.updateOperation = function (priceId, operation, baseField) {
                _.each(this.state.prices, function (price) {
                    if (price.id == priceId) {
                        price.operation = operation;
                        var defBaseField = this.state.priceRelationOptions[price.price_type];
                        if(defBaseField) {
                            defBaseField = defBaseField[0].value;
                        }
                        price.base_field = baseField || defBaseField;
                    }
                }.bind(this));

                this.forceUpdate();
            }.bind(this);

            this.state.updatePriceField = function (priceId, field, value) {
                if(value === '*') {
                    value = null;
                }
                _.each(this.state.prices, function (price) {
                    if (price.id == priceId) {
                        price[field] = value;
                    }
                });
                this.forceUpdate();
            }.bind(this);

            this.state.addBlankPrice = function() {
                var newPrice = {
                    id: guid(),
                    product_id: this.state.productId,
                    price_type: 'base',
                    customer_group_id: null,
                    site_id: null,
                    currency_code: null,
                    amount: null,
                    qty: 1
                };
                this.state.prices.push(newPrice);
                this.forceUpdate();
            }.bind(this);

            calculateDynamicPrice(this.state);
        },
        componentWillMount: function() {
            // TODO: Catch missing important data before component initial render
            if (!this.state.productId) {
                this.state.productId = this.getUrlParamByName('id');
            }

            if (!this.props.id) {
                this.props.id = 'product';
            }
        },
        componentDidMount: function() {
            if (!this.state.prices.length) {
                this.state.addBlankPrice();
            }

            if (this.state.addPriceCallback && typeof window[this.state.addPriceCallback] === 'function') {
                window[this.state.addPriceCallback](this.state.prices, this.state.option);
            }
        },
        componentDidUpdate: function() {
            if (!this.state.prices.length) {
                this.state.addBlankPrice();
            }
        },
        render: function() {
            this.init();
            return (
                <PricesApp {...this.state} id={this.props.id} />
            );
        }
    });

    return Price;
});
