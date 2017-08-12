define(['lodash', 'sv-app-data', 'sv-comp-grid', 'text!sv-page-sales-orders-form-main-tpl'],
    function (_, SvAppData, SvCompGrid, tabMainTpl) {


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

        template: tabMainTpl,
        props: {
            form: {
                default: defForm
            }
		},
        data: function () {
            var data = {
                editing: {customer: false, shipping: false, billing: false, order: false},
                dict: SvAppData,
                itemsGrid: {
                    config: this.form.items_grid_config || {}
                    // rows: this.form.items
                }
            };
            data.itemsGrid.config.data = this.form.items;
            return data;
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
            },
            onEvent: function (type, args) {
                console.log(type, args);
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