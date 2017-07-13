define(['lodash', 'vue', 'sv-hlp', 'text!sv-page-sales-orders-form-shipments-tpl'], function (_, Vue, SvHlp, tabTpl) {
    var Component = {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.formTab],
        props: {
            form: {
                type: Object
            }
        },
        template: tabTpl
    };
    return Component;
});