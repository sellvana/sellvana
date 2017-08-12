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
                edit_mode: false,
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
            paidByStoreCredit: function () {
                return 0;
            }
        },
        methods: {
            regionOptions: function (type) {
                if (!this.form.order.id) {
                    return [];
                }
                return this.dict.regions_seq['@' + this.form.order[type + '_country']];
            },
            enterEditMode: function() {
                this.edit_mode = true;
            },
            saveEditChanges: function () {
                this.edit_mode = false;
            },
            generatePOs: function () {
                var vm = this;
                this.sendRequest('POST', 'multivendor/purchase_orders/generate_pos', {id: this.form.order.id}, function (response) {
                    console.log(response);
                    vm.emitEvent('pos-generated');
                });
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