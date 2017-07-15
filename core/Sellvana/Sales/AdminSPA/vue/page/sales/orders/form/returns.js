define(['lodash', 'vue', 'sv-mixin-form-tab', 'text!sv-page-sales-orders-form-returns-tpl'], function (_, Vue, SvMixinFormTab, tabTpl) {
    var Component = {
        mixins: [SvMixinFormTab],
        props: {
            form: {
                type: Object
            }
        },
        template: tabTpl
    };
    return Component;
});