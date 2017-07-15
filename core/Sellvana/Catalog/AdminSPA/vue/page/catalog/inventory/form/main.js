define(['sv-mixin-form-tab', 'text!sv-page-catalog-inventory-form-main-tpl'], function (SvMixinFormTab, tabMainTpl) {
    return {
        mixins: [SvMixinFormTab],
        template: tabMainTpl,
        props: ['form']
    }
});