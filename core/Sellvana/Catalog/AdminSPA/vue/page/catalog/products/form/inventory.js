define(['sv-hlp', 'text!sv-page-catalog-products-form-inventory-tpl'], function (SvHlp, tabTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabTpl,
        props: ['form']
    }
});