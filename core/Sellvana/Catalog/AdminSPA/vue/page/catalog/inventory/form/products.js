define(['sv-hlp', 'text!sv-page-catalog-inventory-form-products-tpl'], function (SvHlp, tabMainTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabMainTpl,
        props: ['form']
    }
});