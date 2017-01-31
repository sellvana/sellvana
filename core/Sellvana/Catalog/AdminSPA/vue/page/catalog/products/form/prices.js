define(['sv-comp-form-catalog-prices', 'text!sv-page-catalog-products-form-prices-tpl'], function (SvCompPrices, tabMainTpl) {
    return {
        components: {
            'sv-comp-form-catalog-prices': SvCompPrices
        },
        template: tabMainTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        }
    }
});