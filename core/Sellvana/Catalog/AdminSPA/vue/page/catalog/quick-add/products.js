define(['sv-hlp', 'text!sv-page-catalog-quick-add-products-tpl'], function (SvHlp, AddProductsTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: AddProductsTpl
    }
});