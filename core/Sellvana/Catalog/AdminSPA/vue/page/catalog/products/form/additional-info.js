define(['sv-hlp', 'text!sv-page-catalog-products-form-additional-info-tpl'], function (SvHlp, tabProdAdditional) {
    var SvPageCatalogProductsFormAdditionalInfo = {
        mixins: [SvHlp.mixins.common],
        template: tabProdAdditional,
        computed: {

        },
    };
    return SvPageCatalogProductsFormAdditionalInfo;
});