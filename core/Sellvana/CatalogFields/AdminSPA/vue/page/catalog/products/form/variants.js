define(['sv-mixin-common', 'text!sv-page-catalog-products-form-variants-tpl'], function (SvMixinCommon, tabProdVariants) {
    var SvPageCatalogProductsFormVariants = {
        mixins: [SvMixinCommon],
        template: tabProdVariants,
        computed: {

        }
    };
    return SvPageCatalogProductsFormVariants;
});