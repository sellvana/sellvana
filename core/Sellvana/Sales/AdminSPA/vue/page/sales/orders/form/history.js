define(['sv-comp-grid', 'text!sv-page-sales-orders-form-history-tpl', 'json!sv-page-sales-orders-form-history-config'], function (SvCompGrid, tabHistoryTpl, historyGridConfig) {

    return {
        props: {
            form: {
                type: Object
            }
        },
        data: function () {
            if (historyGridConfig.data_url && this.form.order && this.form.order.id) {
                historyGridConfig.data_url = historyGridConfig.data_url.supplant({id: this.form.order.id});
            }
            return {
                grid: {
                    config: historyGridConfig
                }
            }
        },
        template: tabHistoryTpl,
        components: {
            'sv-comp-grid': SvCompGrid
        }
    };
});