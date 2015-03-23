/**
 * Created by pp on 02-26-2015.
 */
define(['jquery', 'underscore', 'react', 'fcom.locale', 'daterangepicker'], function ($, _, React, Locale) {
    var PricesApp = React.createClass({
        render: function () {
            var childProps = _.omit(this.props, ['prices', 'deleted','validatePrices', 'title']);
            return (
                <div id="prices">
                    <h4>{this.props.title}</h4>
                    <table className="table table-striped">
                        <thead>
                        <tr className="table-title">
                            <th></th>
                            {this.props.show_customers? <th>{Locale._("Customer Group")}</th>: null}
                            {this.props.show_sites? <th>{Locale._("Site")}</th>: null}
                            {this.props.show_currency? <th>{Locale._("Currency")}</th>: null}
                            <th>{Locale._("Price Type")}</th>
                            <th>{Locale._("Price")}</th>
                            <th>{Locale._("")}</th>
                            <th>{Locale._("")}</th>
                        </tr>
                        <tr className="table-actions">
                            <td></td>
                            {this.props.show_customers? <td>
                                <select id="filter_customer_group" ref="filter_customer_group" className="form-control to-select2">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.customer_groups, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td>: null}
                            {this.props.show_sites? <td>
                                <select id="filter_site" ref="filter_site" className="form-control to-select2">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.sites, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td>: null}
                            {this.props.show_currency? <td>
                                <select id="filter_currency" ref="filter_currency" className="form-control to-select2">
                                    <option value="*">{Locale._("All (*)")}</option>
                                    {_.map(this.props.currencies, function (val, key) {
                                        return <option key={key} value={key}>{val}</option>
                                    })}
                                </select>
                            </td>: null}
                            <td>
                                <select id="price-types" className="form-control to-select2" ref="price-types">
                                    <option value="-1">{Locale._("Add Price ...")}</option>
                                    {_.map(this.props.price_types, function (pt, pk) {
                                        return <option key={pk} value={pk}
                                                       disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>
                                    })}
                                </select>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody>
                        {_.map(this.props['prices'], function (price) {
                            if (this.props['deleted'] && this.props['deleted'][price.id]) {
                                return <input key={'delete-' + price.id} type="hidden"
                                              name={"price[" + price.id + "][delete]"} value="1"/>
                            }

                            if (this.shouldPriceShow(price) === false) {
                                return <span key={'empty' + price.id}/>;
                            }

                            return <PriceItem data={price} {...childProps} key={price['id']}
                                              validate={this.props.validatePrices}/>
                        }.bind(this))}
                        </tbody>
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
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
        },
        componentDidMount: function () {
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
            $(this.refs['price-types'].getDOMNode()).on("change", this.props.prices_add_new);
            _.each(['filter_customer_group', 'filter_site', 'filter_currency'], function (filter) {
                if (this.refs[filter]) {
                    var $filter = $(this.refs[filter].getDOMNode());
                    $filter.on('change', this.props.applyFilter)
                }
            }.bind(this));
        }
    });

    var PriceItem = React.createClass({
        editable: true,
        render: function () {
            var price = this.props.data;
            this.editable = (this.props.editable_prices.indexOf(price['price_type']) != -1);
            var priceTypes = <span key="price_type">{this.props.price_types[price['price_type']]}</span>;
            if(this.editable) {
                priceTypes =
                    <select key="price_type" className="to-select2 form-control priceUnique"
                        name={this.getFieldName(price, 'price_type')}
                        defaultValue={price['price_type']} ref="price_type">
                            {_.map(this.props.price_types, function (pt, pk) {
                                return <option key={pk} value={pk} disabled={pk == 'promo' ? 'disabled' : null}>{pt}</option>
                            })}
                    </select>;
            }

            var qty = <input key="qty" type="hidden" name={this.getFieldName(price, "qty")} defaultValue={price['qty']}/>;
            if (price['price_type'] === 'tier') {
                qty = <input key="qty" type="text" className="form-control priceUnique" name={this.getFieldName(price, "qty")}  placeholder={Locale._("Amount")}
                             defaultValue={price['qty']} onChange={this.props.validate} readOnly={this.editable ? null : 'readonly'}/>;
            }

            var dateRange = <span key="sale_period"/>;
            if(price['price_type'] === 'sale') {
                dateRange = <input ref="sale_period" key="sale_period" type="text" className="form-control"
                    name={this.getFieldName(price, "sale_period")} placeholder={Locale._("Select sale dates")}
                    defaultValue={price['sale_period']} readOnly={this.editable ? null : 'readonly'}/>;
            }

            var operation = null, baseField = null;
            if(this.props.priceRelationOptions && this.props.priceRelationOptions[price['price_type']]) {
                operation =
                    <select key="operation" name={this.getFieldName(price, 'operation')} defaultValue={price['operation']}
                        ref="operation" className="to-select2">
                        {this.props.operationOptions.map(function (o) {
                            return <option value={o.value} key={o.value}>{o.label}</option>
                        })}
                    </select>;
                if(price['operation'] && price['operation'] !== "=$") {
                    baseField =
                        <select ref="base_fields" key="base_fields" name={this.getFieldName(price, 'base_field')}
                            defaultValue={price['base_field']} className="base_field to-select2">
                            {this.props.priceRelationOptions[price['price_type']].map(function (p) {
                                return <option key={p.value} value={p.value}>{p.label}</option>
                            })}
                        </select>
                }
            }

            return (
                <tr className="price-item">
                    <td>
                        <a href="#" className="btn-remove" data-id={price.id}
                           id={"remove_price_btn_" + price.id}>
                            <span className="icon-remove-sign"></span>
                        </a>
                        { price['product_id'] && price['product_id'] !== "*" ?
                            <input type="hidden" name={this.getFieldName(price, "product_id")}
                                   defaultValue={price['product_id']}/> : null }
                    </td>
                    { this.props.show_customers ? <td>
                        <select name={this.getFieldName(price, "customer_group_id")} disabled={this.editable? null: true}
                                defaultValue={price['customer_group_id']} className={"to-select2" + (this.editable? " priceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.customer_groups, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                    </td>: null }
                    { this.props.show_sites ? <td>
                        <select name={this.getFieldName(price, "site_id")} disabled={this.editable? null: true}
                                defaultValue={price['site_id']} className={"to-select2" + (this.editable? " priceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.sites, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>
                    </td>: null}
                    {this.props.show_currency ? <td>
                        <select name={this.getFieldName(price, "currency_code")} disabled={this.editable? null: true}
                                defaultValue={price['currency_code']} className={"to-select2" + (this.editable? " priceUnique": '')}>
                            <option value="*">{Locale._("Default")}</option>
                            {_.map(this.props.currencies, function (val, key) {
                                return <option key={key} value={key}>{val}</option>
                            })}
                        </select>

                    </td>: null}
                    <td>
                        {priceTypes}
                    </td>
                    <td>
                        <input type="text" className="form-control" name={this.getFieldName(price, "price")}
                               defaultValue={price['price']} readOnly={this.editable ? null: 'readonly'}/>
                    </td>
                    <td>
                        { operation? {operation} : null }
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
            if(this.props.data.operation && this.props.data.operation !== '=$') {
                $('select.base_field', this.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'});
            }
            //var operation = this.refs['operation'];
            //if (operation) {
            //    var self = this;
            //    $(operation.getDOMNode()).select2({minimumResultsForSearch: 15, width: 'resolve'}).on('change', function (e) {
            //        var operation = $(e.target).val();
            //        var id = self.props.data.id;
            //        self.props.updateOperation(id, operation);
            //    })
            //}
            this.initPrices();
        },
        componentWillUpdate: function () {
            if(this.refs['base_fields']) {
                $(this.refs['base_fields'].getDOMNode()).select2('destroy');
            }
        },
        initPrices: function () {
            var self = this;
            if (this.editable) {
                $(this.refs['price_type'].getDOMNode()).on('change', function (e) {
                    e.stopPropagation();
                    var priceType = $(e.target).val();
                    var id = self.props.data.id;
                    self.props.updatePriceType(id, priceType);
                    self.props.validate();
                });
                $('a.btn-remove', this.getDOMNode()).on('click', function (e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    //console.log(id);
                    self.props.deletePrice(id);
                });

                if(this.props.data['price_type'] === 'sale'){
                    this.initDateInput();
                }

                var operation = this.refs['operation'];
                if (operation) {
                    $(operation.getDOMNode()).on('change', function (e) {
                        var operation = $(e.target).val();
                        var id = self.props.data.id;
                        self.props.updateOperation(id, operation);
                    })
                }
            }
        },
        initDateInput: function () {
            var data = this.props.data['sale_period'], s, e;
            var dateField = this.refs['sale_period'];
            if (!data) {
                var startDate = new Date();
                s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            } else {
                var dates = data.split(" - ");
                s = dates[0];
                e = dates[1] || dates[0];
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
    var productPrice = {
        options: {
            title: Locale._("Product Prices")
        },
        newIdx: 0,
        init: function (options) {
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
                if(options.prices_add_new && options.prices_add_new.length) {
                    console.log(allowed);
                    if (allowed) {
                        options.prices_add_new.attr('disabled', false);
                    } else {
                        options.prices_add_new.attr('disabled', 'disabled');
                    }
                }
            };
            this.options.applyFilter = function (e) {
                var $el = $(e.target);
                var filter = $el.attr('id');
                this.options[filter + '_value'] = $el.val();
                React.render(<PricesApp {...this.options}/>, this.options.container[0]);
            }.bind(this);

            //checkAddAllowed(this.options);

            this.options.prices_add_new = function (e) {
                e.preventDefault();
                var type = $(e.target).val();
                $(e.target).select2("val", "-1", false);

                var newPrice = {
                    id: 'new_' + (this.newIdx++),
                    product_id: this.options.product_id,
                    price_type: type,
                    customer_group_id: this.options.filter_customer_group_value || null,
                    site_id: this.options.filter_site_value || null,
                    currency_code: this.options.filter_currency_value || null,
                    price: 0.0,
                    qty: 1
                };
                if(!this.options.prices) {
                    this.options.prices = [];
                }
                this.options.prices.push(newPrice);

                React.render(<PricesApp {...this.options}/>, this.options.container[0]);
            }.bind(this);


            this.options.deletePrice = function (id) {
                if (!this.options['deleted']) {
                    this.options['deleted'] = {};
                }
                this.options['deleted'][id] = true;
                React.render(<PricesApp {...this.options} />, this.options.container[0])
            }.bind(this);

            this.options.updatePriceType = function (price_id, price_type) {
                _.each(this.options.prices, function (price) {
                    if (price.id == price_id) {
                        price.price_type = price_type;
                    }
                });

                React.render(<PricesApp {...this.options} />, this.options.container[0])
            }.bind(this);

            this.options.updateOperation = function (price_id, operation) {
                _.each(this.options.prices, function (price) {
                    if (price.id == price_id) {
                        price.operation = operation;
                    }
                });
                $("#price").find(".to-select2").select2('destroy');
                React.render(<PricesApp {...this.options} />, this.options.container[0])
            }.bind(this);

            React.render(<PricesApp {...this.options} />, container[0])
        }
    };
    return productPrice;
});
