/**
 * Created by pp on 02-26-2015.
 */
define(['jquery', 'underscore', 'react', 'fcom.locale'], function ($, _, React, Locale) {
    var PricesApp = React.createClass({displayName: "PricesApp",
        render: function () {
            return (
                React.createElement("div", {id: "prices"}, React.createElement("h4", null, this.props.title), 
                    React.createElement("div", {className: "form-group", id: "price_captions"}, 
                        React.createElement("div", {style: divStyle}, Locale._("Price Type")), 
                        React.createElement("div", {style: divStyle}, Locale._("Customer group")), 
                        React.createElement("div", {style: divStyle}, Locale._("Site")), 
                        React.createElement("div", {style: divStyle}, Locale._("Currency")), 
                        React.createElement("div", {style: divStyle}, Locale._("Price")), 
                        React.createElement("div", {style: divStyle}, Locale._("Qty (only tier prices)"))
                    ), 
                    _.map(this.props['prices'], function (price) {
                        if(this.props['deleted'] && this.props['deleted'][price.id]) {
                            return React.createElement("input", {key: 'delete-' + price.id, type: "hidden", 
                                          name: "price[" + price.id + "][delete]", value: "1"})
                        }
                        var show = true;
                        if(this.props['filter_customer_group_value'] && this.props['filter_customer_group_value'] !== '*' && this.props['filter_customer_group_value'] != price['customer_group_id']) {
                            show = false;
                        }
                        if(this.props['filter_site_value'] && this.props['filter_site_value'] !== '*' && this.props['filter_site_value'] != price['site_id']) {
                            show = false;
                        }
                        if(this.props['filter_currency_value'] && this.props['filter_currency_value'] !== '*' && this.props['filter_currency_value'] != price['currency_id']) {
                            show = false;
                        }

                        if(price.id === undefined) {
                            price.id = 'new_' + this.newIdx++;
                        }

                        if(show === false) {
                            return React.createElement("span", {key: 'empty'+price.id});
                        }
                        var qty = React.createElement("input", {type: "hidden", name: this.getFieldName(price, "qty"), defaultValue: price['qty']});
                        if(price['price_type'] === 'tier') {
                            qty = React.createElement("input", {type: "text", className: "form-control", name: this.getFieldName(price, "qty"), defaultValue: price['qty']});
                        }
                        return (
                            React.createElement("div", {className: "form-group", key: price['id']}, 
                                React.createElement("div", {style: divStyle}, 
                                    React.createElement("a", {href: "#", className: "btn-remove", "data-id": price.id, 
                                       id: "remove_price_btn_" + price.id}, 
                                        React.createElement("span", {className: "icon-remove-sign"})
                                    ), 
                                    React.createElement("select", {className: "to-select2 form-control", name: this.getFieldName(price, 'price_type'), defaultValue: price['price_type']}, 
                                    _.map(this.props.price_types, function (pt, pk) {
                                        return React.createElement("option", {key: pk, value: pk}, pt)
                                    })
                                    )
                                ), 
                                React.createElement("div", {style: divStyle}, 
                                    React.createElement("input", {type: "hidden", name: this.getFieldName(price, "product_id"), defaultValue: price['product_id']}), 
                                    React.createElement("input", {type: "hidden", name: this.getFieldName(price, "customer_group_id"), defaultValue: price['customer_group_id']}), 
                                    React.createElement("input", {type: "text", className: "form-control", readOnly: "readOnly", defaultValue: this.getCustomerGroupName(price['customer_group_id'])})
                                ), 
                                React.createElement("div", {style: divStyle}, 
                                    React.createElement("input", {type: "hidden", name: this.getFieldName(price, "site_id"), defaultValue: price['site_id']}), 
                                    React.createElement("input", {type: "text", className: "form-control", readOnly: "readOnly", defaultValue: this.getSiteName(price['site_id'])})
                                ), 
                                React.createElement("div", {style: divStyle}, 
                                    React.createElement("input", {type: "hidden", name: this.getFieldName(price, "currency_id"), defaultValue: price['currency_id']}), 
                                    React.createElement("input", {type: "text", className: "form-control", readOnly: "readOnly", defaultValue: this.getCurrencyName(price['currency_id'])})
                                ), 
                                React.createElement("div", {style: divStyle}, 
                                    React.createElement("input", {type: "text", className: "form-control", name: this.getFieldName(price, "price"), defaultValue: price['price']})
                                ), 
                                React.createElement("div", {style: divStyle}, 
                                    qty
                                )
                            )
                        )
                    }.bind(this))
                )
            );
        },
        componentDidMount: function () {
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15});
            var self = this;
            $('a.btn-remove', this.getDOMNode()).on('click', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                console.log(id);
                self.props.deletePrice(id);
            });
        },
        newIdx: 0,
        getFieldName: function (obj, field) {
            return "prices[productPrice][" + obj['id'] + "][" + field + "]";
        },
        _getPropOptionLabel: function (option, id) {
            if(null === id || undefined === id || false === id) {
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
    var productPrice = {
        options: {
            price_types: { regular:"Regular", map:"MAP", msrp:"MSRP", sale:"Sale", tier:"Tier" },
            title: Locale._("Product Prices"),
            deletePrice: function (id) {
                if (!this.options['deleted']) {
                    this.options['deleted'] = {};
                }
                this.options['deleted'][id] = true;
                React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0])
            }.bind(this)
        },
        init: function (options) {
            this.options = _.extend({}, this.options, options);

            var container = this.options.container;
            if(!container || !container.length) {
                console.log("Prices div container not found");
                return;
            }

            _.each(['filter_customer_group', 'filter_site', 'filter_currency'], function (filter) {
                if (this.options[filter].length) {
                    this.options[filter + '_value'] = this.options[filter].val();
                    this.options[filter].on('change', function (e) {
                        e.preventDefault();
                        this.options[filter + '_value'] = $(e.target).val();
                        React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0]);
                    }.bind(this));
                }
            }.bind(this));

            if(this.options.prices_add_new && this.options.prices_add_new.length) {
                this.options.prices_add_new.on('click', function (e) {
                    e.preventDefault();
                    var newPrice = {
                        price_type: 'regular',
                        customer_group_id: this.options.filter_customer_group_value || null,
                        site_id: this.options.filter_site_value || null,
                        currency_id: this.options.filter_currency_value || null,
                        price: 0.0,
                        qty: 1
                    };
                    if(!this.options.prices) {
                        this.options.prices = [];
                    }
                    this.options.prices.push(newPrice);
                    React.render(React.createElement(PricesApp, React.__spread({},  this.options)), this.options.container[0]);
                }.bind(this));
            }

            React.render(React.createElement(PricesApp, React.__spread({},  this.options)), container[0])
        }
    };
    return productPrice;
});
