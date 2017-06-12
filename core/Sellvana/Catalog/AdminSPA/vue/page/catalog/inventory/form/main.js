define(['sv-hlp', 'text!sv-page-catalog-inventory-form-main-tpl'], function (SvHlp, tabMainTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabMainTpl,
        props: ['form']
    }
});