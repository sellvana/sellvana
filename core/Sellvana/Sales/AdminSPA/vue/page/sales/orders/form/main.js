define(['sv-hlp', 'sv-comp-grid', 'text!sv-page-sales-orders-form-main-tpl', 'json!sv-page-sales-orders-form-items-config'],
    function (SvHlp, SvCompGrid, tabMainTpl, itemsGridConfig) {

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
                dict: SvAppData
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
            itemsGrid: function () {
                return {
                    config: itemsGridConfig,
                    rows: this.form.items
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