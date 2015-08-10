/**
 * Created by pp on 02-26-2015.
 */
define(['jquery', 'underscore', 'react', 'fcom.locale', 'jquery.validate', 'daterangepicker'], function ($, _, React, Locale) {
    var PriceItem = React.createClass({displayName: "PriceItem",
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
                if(this.props.data.price_type === 'sale'){
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
            if (this.refs.base_fields) {
                baseField = $(this.refs.base_fields.getDOMNode()).val();
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
        render: function() {
            var price = this.props.data;
            this.editable = this.checkEditable(price);
            var priceTypes =
                React.createElement("span", {key: "price_type_wrapper"}, 
                    React.createElement("select", {key: "price_type", className: "form-control price-type " + (this.editable || this.props.theBase ? this.props.id + "PriceUnique": ''), name: this.getFieldName(price, 'price_type'), disabled: !this.editable, defaultValue: price.price_type, ref: "price_type"}, 
                            _.map(this.props.price_types, function (pt, pk) {
                                return React.createElement("option", {key: pk, value: pk, disabled: pk == 'promo' ? 'disabled' : null}, pt);
                            })
                    ), 
                    !this.editable ? React.createElement("input", {type: "hidden", value: price.price_type, name: this.getFieldName(price, 'price_type')}) : null
                );

            var qty = React.createElement("input", {key: "qty_hidden", type: "hidden", name: this.getFieldName(price, "qty"), defaultValue: price.qty});
            if (price.price_type === 'tier') {
                qty = React.createElement("label", {key: "qty_label"}, Locale._("Qty"), React.createElement("div", {style: {display: "inline-block", width:"30%", margin:"0 0 0 5px"}}, React.createElement("input", {key: "qty", type: "number", step: "1", className: "form-control "+this.props.id+"PriceUnique", name: this.getFieldName(price, "qty"), placeholder: Locale._("Qty"), defaultValue: price.qty, onChange: this.props.validate, size: "2", readOnly: this.editable ? null : 'readonly'})));
            }

            var dateRange = React.createElement("span", {key: "sale_period"});
            if(price.price_type === 'sale') {
                var dates = "";
                if(price.valid_from) {
                    dates += price.valid_from;
                    if(price.valid_to) {
                        dates += this.props.sale_date_separator ? this.props.sale_date_separator : '/';
                        dates += price.valid_to;
                    }
                }
                dateRange = React.createElement("input", {ref: "sale_period", key: "sale_period", type: "text", className: "form-control "+this.props.id+"PriceUnique", name: this.getFieldName(price, "sale_period"), placeholder: Locale._("Select sale dates"), defaultValue: dates, readOnly: this.editable ? null : 'readonly'});
            }

            var operation = null, baseField = null;
            if(this.props.priceRelationOptions && this.props.priceRelationOptions[price.price_type]) {
                var label = _.find(this.props.operationOptions, function (item) {
                    return price.operation == item.value;
                });
                operation =
                        React.createElement("select", {key: "operation", name: this.getFieldName(price, 'operation'), defaultValue: price.operation, 
                            ref: "operation", className: "form-control", disabled: price.price_type == 'promo', onChange: this.updateOperation}, 
                            this.props.operationOptions.map(function (o) {
                                return React.createElement("option", {value: o.value, key: o.value}, o.label);
                            })
                        );
                if(price.operation && price.operation !== "=$") {
                    baseField =
                        React.createElement("select", {ref: "base_fields", key: "base_fields", name: this.getFieldName(price, 'base_field'), defaultValue: price.base_field, className: "base_field form-control", onChange: this.updateOperation, disabled: this.editable || this.props.theBase ? null: true}, 
                            this.props.priceRelationOptions[price.price_type].map(function (p) {
                                return React.createElement("option", {key: p.value, value: p.value}, p.label);
                            })
                        );
                }
            }

            var groups = null, sites = null, currencies = null;
            if(this.props.show_customers) {
                groups =
                    React.createElement("span", {key: "cuatomer_groups"}, 
                        React.createElement("select", {name: this.getFieldName(price, "customer_group_id"), disabled: this.editable? null: true, onChange: this.updatePrice, defaultValue: price.customer_group_id, "data-type": "customer_group_id", className: "form-control customer-group " + (this.editable ? this.props.id + "PriceUnique" : '')}, 
                            React.createElement("option", {value: "*"}, Locale._("Default")), 
                            _.map(this.props.customer_groups, function (val, key) {
                                return React.createElement("option", {key: key, value: key}, val);
                            })
                        ), 
                        !this.editable ? React.createElement("input", {type: "hidden", name: this.getFieldName(price, "customer_group_id"), value: price.customer_group_id}) : null
                    );
            }
            if(this.props.show_sites) {
                sites =
                    React.createElement("span", {key: "sites"}, 
                        React.createElement("select", {name: this.getFieldName(price, "site_id"), disabled: this.editable? null: true, defaultValue: price.site_id, onChange: this.updatePrice, "data-type": "site_id", className: "form-control site " + (this.editable ? this.props.id + "PriceUnique": '')}, 
                            React.createElement("option", {value: "*"}, Locale._("Default")), 
                            _.map(this.props.sites, function (val, key) {
                                return React.createElement("option", {key: key, value: key}, val);
                            })
                        ), 
                        !this.editable? React.createElement("input", {type: "hidden", name: this.getFieldName(price, "site_id"), value: price.site_id}): null
                    );
            }
            if(this.props.show_currency) {
                currencies =
                    React.createElement("span", null, 
                        React.createElement("select", {name: this.getFieldName(price, "currency_code"), disabled: this.editable? null: true, defaultValue: price.currency_code, onChange: this.props.updatePrice, "data-type": "currency_code", className: "form-control currency " + (this.editable? this.props.id + "PriceUnique": '')}, 
                            React.createElement("option", {value: "*"}, Locale._("Default")), 
                            _.map(this.props.currencies, function (val, key) {
                                return React.createElement("option", {key: key, value: key}, val);
                            })
                        ), 
                        !this.editable? React.createElement("input", {type: "hidden", name: this.getFieldName(price, "currency_code"), value: price.currency_code}): null
                    );
            }

            return (
                React.createElement("tr", {className: this.props.id + "-price-item"}, 
                    React.createElement("td", null, 
                        this.editable ? React.createElement("a", {href: "javascript:void(0)", className: "btn-remove", "data-id": price.id, id: "remove_price_btn_" + price.id, onClick: this.props.deletePrice}, " ", React.createElement("span", {className: "icon-remove-sign"})) : null, 
                        this.props.variant_id ? React.createElement("input", {type: "hidden", defaultValue: this.props.variant_id, name: this.getFieldName(price, "variant_id")}) : null, 
                         price.product_id && price.product_id !== "*" ? React.createElement("input", {type: "hidden", name: this.getFieldName(price, "product_id"), defaultValue: price.product_id}) : null
                    ), 
                     this.props.show_customers ? React.createElement("td", null, groups): null, 
                     this.props.show_sites ? React.createElement("td", null, sites): null, 
                     this.props.show_currency ? React.createElement("td", null, currencies): null, 
                    React.createElement("td", null, priceTypes), 
                    React.createElement("td", null, 
                        React.createElement("input", {type: "text", className: "form-control", name: this.getFieldName(price, "amount"), size: "6", onBlur: this.updatePrice, "data-type": "amount", defaultValue: price['amount'], readOnly: this.editable || this.props.theBase ? null: 'readonly'})
                    ), 
                    React.createElement("td", null, 
                         operation ? {operation} : null, 
                         baseField ? {baseField} : null
                    ), 
                    React.createElement("td", null, 
                        [qty, dateRange]
                    ), 
                    React.createElement("td", null, 
                        price.calc_amount ? React.createElement("span", {className: "help-block"}, price.calc_amount.toFixed(2)) : null
                    )
                )
            );
        }
    });

    var PricesApp = React.createClass({displayName: "PricesApp",
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
                React.createElement("div", {id: this.props.id + "-prices"}, 
                    React.createElement("h4", null, this.props.title), 
                    React.createElement("table", {className: "table table-striped "+this.props.id+"-prices-table"}, 
                        React.createElement("thead", null, 
                        React.createElement("tr", {className: "table-title"}, 
                            React.createElement("th", null), 
                            this.props.show_customers? React.createElement("th", null, Locale._("Customer Group")): null, 
                            this.props.show_sites? React.createElement("th", null, Locale._("Site")): null, 
                            this.props.show_currency? React.createElement("th", null, Locale._("Currency")): null, 
                            React.createElement("th", null, Locale._("Price Type")), 
                            React.createElement("th", null, Locale._("Amount")), 
                            React.createElement("th", null, Locale._("")), 
                            React.createElement("th", null, Locale._(""))
                        ), 
                        showFilters? React.createElement("tr", {className: "table-actions", style: {backgroundColor: "#ccc"}}, 
                            React.createElement("td", null), 
                            this.props.show_customers ? React.createElement("td", null, 
                                React.createElement("select", {id: "filter_customer_group", ref: "filter_customer_group", className: "form-control", onChange: this.props.applyFilter}, 
                                    React.createElement("option", {value: "*"}, Locale._("All (*)")), 
                                    _.map(this.props.customer_groups, function (val, key) {
                                        return React.createElement("option", {key: key, value: key}, val);
                                    })
                                )
                            ) : null, 
                            this.props.show_sites ? React.createElement("td", null, 
                                React.createElement("select", {id: "filter_site", ref: "filter_site", className: "form-control", onChange: this.props.applyFilter}, 
                                    React.createElement("option", {value: "*"}, Locale._("All (*)")), 
                                    _.map(this.props.sites, function (val, key) {
                                        return React.createElement("option", {key: key, value: key}, val);
                                    })
                                )
                            ) : null, 
                            this.props.show_currency ? React.createElement("td", null, 
                                React.createElement("select", {id: "filter_currency", ref: "filter_currency", className: "form-control", onChange: this.props.applyFilter}, 
                                    React.createElement("option", {value: "*"}, Locale._("All (*)")), 
                                    _.map(this.props.currencies, function (val, key) {
                                        return React.createElement("option", {key: key, value: key}, val);
                                    })
                                )
                            ) : null, 
                            React.createElement("td", null), 
                            React.createElement("td", {colSpan: "4"})
                        ): null
                        ), 
                        React.createElement("tbody", null, 
                            _.map(this.props.prices, function (price) {
                                if (self.props.deleted && self.props.deleted[price.id]) {
                                    return React.createElement("input", {key: 'delete-' + price.id, type: "hidden", name: "prices[delete][]", value: price.id});
                                }

                                if (self.shouldPriceShow(price) === false) {
                                    return React.createElement("span", {key: 'empty' + price.id});
                                }

                                var theBase = false;
                                if(!baseFound) {
                                    // if price type is base and site, currency and group are null, this is The base price?!
                                    theBase = baseFound = (price.price_type == 'base') && (price.customer_group_id === null) && (price.site_id === null) && (price.currency_code === null);
                                }

                                return React.createElement(PriceItem, React.__spread({data: price, id: self.props.id},  childProps, {key: price.id, priceOptions: priceOptions, theBase: theBase, updatePriceType: self.props.updatePriceType, updatePriceField: self.props.updatePriceField, updateOperation: self.props.updateOperation, deletePrice: self.props.deletePrice, validate: self.props.validatePrices}));
                            })
                        ), 
                        React.createElement("tfoot", null, 
                            React.createElement("tr", {className: "table-actions", style: {backgroundColor: "#ccc"}}, 
                                React.createElement("td", null), 
                                React.createElement("td", null, 
                                    React.createElement("select", {id: "price-types", className: "form-control", ref: "price-types", onChange: this.props.addNewPrice}, 
                                    React.createElement("option", {value: "-1"}, Locale._("Add Price ...")), 
                                    _.map(priceOptions, function (pt, pk) {
                                        return React.createElement("option", {key: pk, value: pk, disabled: pk == 'promo' ? 'disabled' : null}, pt);
                                    })
                                    )
                                ), 
                                React.createElement("td", {colSpan: colspan})
                            )
                        )
                    )
                )
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
                id: '',
                options: {}
            };
        },
        /**
         * Init prices data
         * 
         * @return mixed
         */
        componentDidMount: function() {
            var self = this;
            if (typeof this.props.options.prices === 'undefined' || this.props.options.prices.length === 0) {
                this.props.options.prices = [];
                // If prices has no data and has data source then get data from source
                if (typeof this.props.options.source !== 'undefined') {
                    $.get(this.props.options.source, function(r) {
                        if (r.length) {
                            self.props.options.prices = r;
                            self.setState({ options: self.props.options });
                        }
                    });
                }

                // If prices still null || has no data source then add new base one
                if (this.props.options.prices.length === 0) {
                    var newPrice = {
                        id: 'new_' + (this.newIdx++),
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
                }
            } else {
                // Todo: If prices has data
            }
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
        addNewPrice: function(e) {
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
            var base_field = price.base_field;
            var customer_group_id = price.customer_group_id;
            var currency_code = price.currency_code;
            var site_id = price.site_id;

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
        },
        calculateDynamicPrice: function(options) {
            var self = this;
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
                React.createElement(PricesApp, React.__spread({},  this.props.options, {id: this.props.id, addNewPrice: this.addNewPrice, updatePriceType: this.updatePriceType, updatePriceField: this.updatePriceField, updateOperation: this.updateOperation, applyFilter: this.applyFilter, deletePrice: this.deletePrice}))
            );
        }
    });
    return VariantPrice;
});
