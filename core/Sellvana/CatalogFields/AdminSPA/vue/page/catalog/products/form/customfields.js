define(['sv-hlp', 'text!sv-page-catalog-products-form-customfields-tpl'], function (SvHlp, tpl) {
    var SvPageCatalogProductsFormCustomFields = {
        mixins: [SvHlp.mixins.common],
        template: tpl,
        computed: {

        }
    };

    return SvPageCatalogProductsFormCustomFields;
});