define(['sv-hlp', 'sv-comp-grid',  'text!sv-page-sales-orders-form-purchase-orders-tpl', 'json!sv-page-sales-orders-form-purchase-orders-grid-config'],
    function (SvHlp, SvCompGrid, tabPurchaseOrdersTpl, poGridConfig) {

    return {
        mixins: [SvHlp.mixins.common],
        props: {
            form: {
                type: Object
            }
        },
        data: function () {
            if (poGridConfig.data_url && this.form.order && this.form.order.id) {
                poGridConfig.data_url = poGridConfig.data_url.supplant({id: this.form.order.id});
            }
            return {
                grid: {
                    config: poGridConfig
                },
                poEditMode: false
            }
        },
        methods: {
            toggleEditing: function(type) {
                this.editing[type] = !this.editing[type];
            },
            switchToPoEdit: function (poId) {
                this.poEditMode = true;
            },
            switchToPoGrid: function () {
                this.poEditMode = false;
            }
        },
        template: tabPurchaseOrdersTpl,
        components: {
            'sv-comp-grid': SvCompGrid
        }

    };
});