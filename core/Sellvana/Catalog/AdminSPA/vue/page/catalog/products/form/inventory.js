define(['sv-mixin-form-tab', 'text!sv-page-catalog-products-form-inventory-tpl'], function (SvMixinFormTab, tabTpl) {
    return {
        mixins: [SvMixinFormTab],
        template: tabTpl,
        props: ['form']
    }
});