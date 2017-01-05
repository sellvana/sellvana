define(['sv-comp-grid', 'text!sv-page-sales-orders-form-history-tpl'], function (SvCompGrid, tabHistoryTpl) {

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
        props: {
            form: {
                default: defForm
            }
        },
        computed: {
            grid: function () {
                return {
                    config: {
                        id: 'sales_order_histoy',
                        data_url: 'orders/form_history_grid_data?id=' + this.form.order.id,
                        columns: [
                            {field:'id', label:'ID'},
                            {field:'create_at', label:'Timestamp'}
                        ]
                    }
                }
            }
        },
        template: tabHistoryTpl,
        components: {
            'sv-comp-grid': SvCompGrid
        }
    };
});