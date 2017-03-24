define(['sv-hlp', 'sv-comp-grid',  'text!sv-page-sales-orders-form-purchase-tpl'], function (SvHlp, SvCompGrid, tabPurchaseTpl) {

    return {
        mixins: [SvHlp.mixins.common],
        props: {
            form: {
                type: Object
            }
        },
        methods: {
            toggleEditing: function(type) {
                this.editing[type] = !this.editing[type];
            }
        },
        template: tabPurchaseTpl,
        components: {
            'sv-comp-grid': SvCompGrid
        }

    };
});