define(['lodash', 'sv-hlp', 'sv-comp-grid', 'text!sv-page-sales-orders-form-main-tpl'],
    function (_, SvHlp, SvCompGrid, tabMainTpl) {

    var defForm = {
        options: {},
        updates: {},
        tabs: [],

        order: {},
        items: {},
        shipments: {},
        payments: {},
        returns: {},
        refunds: {},
        cancellations: {}
    };

    return {
        mixins: [SvHlp.mixins.common],
        template: tabMainTpl,
        props: {
            form: {
                default: defForm
            }
        },
        data: function () {
            return {
                editing: {customer: false, shipping: false, billing: false, order: false},
                dict: SvAppData,
                itemsGrid: {
                    config: _.get(this.form, 'items_grid_config', {}),
                    rows: this.form.items
                }
            }
        },
        computed: {
            regionOptions: function () {
                return function (type) {
                    if (!this.form.order.id) {
                        return [];
                    }
                    return this.dict.regions_seq['@' + this.form.order[type + '_country']];
                }
            },
            paidByStoreCredit: function () {
                return 0;
            }
        },
        methods: {
            toggleEditing: function(type) {
                this.editing[type] = !this.editing[type];
            }
        },
        watch: {
            'form.order': function () {
                console.log(this.form.order.shipping_country);
            }
        },
        components: {
            'sv-comp-grid': SvCompGrid
        }
    };
});