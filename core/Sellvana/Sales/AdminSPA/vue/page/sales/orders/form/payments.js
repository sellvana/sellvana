define(['sv-hlp', 'sv-comp-grid',  'text!sv-page-sales-orders-form-payments-tpl'], function (SvHlp, SvCompGrid, tabPaymentsTpl) {

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
        template: tabPaymentsTpl,
        components: {
            'sv-comp-grid': SvCompGrid
        }

    };
});