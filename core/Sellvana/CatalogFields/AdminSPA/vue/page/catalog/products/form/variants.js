define(['sv-hlp', 'text!sv-page-catalog-products-form-variants-tpl'], function (SvHlp, tabProdVariants) {
    var SvPageCatalogProductsFormVariants = {
        mixins: [SvHlp.mixins.common],
        template: tabProdVariants,
        computed: {

        }
    };
    return SvPageCatalogProductsFormVariants;
});