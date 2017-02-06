define(['lodash', 'vue', 'text!sv-comp-form-catalog-prices-tpl', 'inline-param'], function (_, Vue, pricesTpl) {
    var SvCompFormCatalogPrices = {
        template: pricesTpl,
        props: ['options', 'prices'],
        data: function () {
            return {
                new_price_type: '',
                filter: {
                    customer_group_id: '',
                    site_id: '',
                    currency_code: ''
                },
                errors: []
            }
        },
        computed: {
            visiblePrices: function () {
                var i, l, prices = [], p, f = this.filter;
                if (f.customer_group_id === '' && f.site_id === '' && f.currency_code === '') {
                    return this.prices;
                }
                for (i = 0, l = this.prices.length; i < l; i++) {
                    p = this.prices[i];
                    if (p.customer_group_id === f.customer_group_id && p.site_id === f.site_id && p.currency_code === f.currency_code) {
                        prices.push(p);
                    }
                }
                return prices;
            },
            priceOperations: function () {
                return [
                    {id:'=$', text:'Equals amount', label:'Equals'},
                    {id:'*$', text:'Multiply by amount', label:'Multiply'},
                    {id:'+$', text:'Add amount to', label:'Add'},
                    {id:'-$', text:'Subtract amount from', label:'Subtract'},
                    {id:'=%', text:'Equals % of', label:'Equals'},
                    {id:'+%', text:'Add % to', label:'Add'},
                    {id:'-%', text:'Subtract % from', label:'Subtract'}
                ];
            }
        },
        methods: {
            availableBaseFields: function (p) {
                var i, j, l = this.prices.length, pr, f, bfs = [];
                for (j in this.options.price_relations[p.price_type]) {
                    pr = this.options.price_relations[p.price_type][j];
                    for (i = 0; i < l; i++) {
                        if (this.prices[i].price_type === pr.value) {
                            bfs.push({id: pr.value, text: pr.label});
                            break;
                        }
                    }
                }
                return bfs;
            },
            pricePreview: function (p, circRef) {
                var basePrice = 0, i, l, p1;
                p.error = false;
                if (p.base_field) {
                    var isDefault = !p.site_id && !p.customer_group_id && !p.currency_code, foundPrices = [];
                    for (i = 0, l = this.prices.length; i < l; i++) {
                        p1 = this.prices[i];
                        if (p1.price_type !== p.base_field) {
                            continue;
                        }
                        if (isDefault && !p1.site_id && !p1.customer_group_id && !p1.currency_code) {
                            basePrice = this.pricePreview(p1, (circRef||0) + 1);
                            break;
                        }
                        if (!isDefault) {
                            p1.specificity = (p1.site_id ? 1 : 0) + (p1.customer_group_id ? 1 : 0) + (p1.currency_code ? 1 : 0);
                            foundPrices.push(p1);
                        }
                    }
                    if (!isDefault && !_.isEmpty(foundPrices)) {
                        foundPrices = _.sortBy(foundPrices, function (o) { return 3 - o.specificity; });
                        basePrice = this.pricePreview(foundPrices[0], (circRef||0) + 1);
                    }
                }
                switch (p.operation) {
                    case '=$': return p.amount; break;
                    case '*$': return basePrice * p.amount; break;
                    case '+$': return basePrice + p.amount; break;
                    case '-$': return basePrice - p.amount; break;
                    case '*%': return basePrice * p.amount / 100; break;
                    case '+%': return basePrice * (1 + p.amount / 100); break;
                    case '-%': return basePrice * (1 - p.amount / 100); break;
                }
            },
            removePrice: function (p) {
                for (var i = 0, l = this.prices.length; i < l; i++) {
                    if (_.isEqual(this.prices[i], p)) {
                        this.prices.splice(i, 1);
                        break;
                    }
                }
            },
            calcPricesHierarchy: function () {
                var i, l = this.prices.length, p, j, p1, pp, result = {};
                for (i = 0; i < l; i++) {
                    p = this.prices[i];
                    p.uid = (p.site_id || '*') + '-' + (p.customer_group_id || '*') + '-' + (p.currency_code || '*') + '-' + p.price_type;
                    p.specificity = (p.site_id ? 0 : 1) + (p.customer_group_id ? 0 : 1) + (p.currency_code ? 0 : 1);
                    p.is_default = !p.site_id && !p.customer_group_id && !p.currency_code;
                }
                for (i = 0; i < l; i++) {
                    p = this.prices[i];
                    if (p.price_type !== 'fixed' && p.base_field) {
                        p.possible_parents = [];
                        for (j = 0; j < l; j++) {
                            p1 = this.prices[j];
                            if (p.base_field !== p1.price_type) {
                                continue;
                            }
                            if (p.is_default) {
                                if (!p1.is_default) {
                                    continue;
                                }
                            } else {
                                if ((p.site_id && p1.site_id && p.site_id !== p1.site_id)
                                    || (p.customer_group_id && p1.customer_group_id && p.customer_group_id !== p1.customer_group_id)
                                    || (p.currency_code && p1.currency_code && p.currency_code !== p1.currency_code)
                                ) {
                                    continue;
                                }
                            }
                            p.possible_parents.push(this.prices[j]);
                        }
                    }
                }
                for (i = 0; i < l; i++) {
                    p = this.prices[i];
                    if (p.possible_parents) {
                        pp = _.sortBy(p.possible_parents, function (o) { return o.specificity; });
                        p.first_parent = pp[0];
                    }
                }
            },
            validatePrices: function () {
                var i, l, j, p, bp, hasFixedPrice = false, errors = [], used = {};
                for (i = 0, l = this.prices.length; i < l; i++) {
                    p = this.prices[i];
                    p.error = null;
                    if (p.price_type === 'fixed') {
                        hasFixedPrice = true;
                    } else {
                        used = {};
                        bp = p;
                        for (j = 0; j < 10; j++) {
                            if (!bp.parent) { //TODO: validate all possible parents

                            }
                            if (used[p.uid]) {
                                p.error = 'Circular reference detected';
                                break;
                            }
                            used[p.uid] = true;
                        }
                    }
                }
                if (!hasFixedPrice) {
                    this.errors.push('Need to have at least one fixed price');
                }
            },

            isDefault: function (p) {
                return !p.site_id && !p.customer_group_id && !p.currency_code;
            },
            inlineOptions: function (type) {
                var i, l, options = [], o;
                if (!this.options[type]) {
                    return [];
                }
                for (i = 0, l = this.options[type].length; i < l; i++) {
                    o = this.options[type][i];
                    options.push({
                        id: o.id === '' ? '' : o.id,
                        text: o.text === '' ? '...' : o.text
                    });
                }
                return options;
            },
            textBetweenParams: function (p) {
                var map = {
                    '=$': '',
                    '*$': 'times',
                    '+$': 'to',
                    '-$': 'from',
                    '*%': '% of',
                    '+%': '% to',
                    '-%': '% from'
                };
                return map[p.operation] || '';
            }
        },
        watch: {
            new_price_type: function (new_price_type) {
                if (new_price_type) {
                    this.prices.push({
                        site_id: null,
                        customer_group_id: null,
                        currency_code: null,
                        price_type: new_price_type,
                        operation: '=$'
                    });
                    this.new_price_type = '';
                }
            },
            'form.prices': {
                deep: true,
                handler: function (prices) {
                    this.calcPricesHierarchy();
                    this.validatePrices();
                }
            }
        }
    };

    Vue.component('sv-comp-form-catalog-prices', SvCompFormCatalogPrices);

    return SvCompFormCatalogPrices;
});