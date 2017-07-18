define(['sv-comp-form-catalog-prices', 'text!sv-page-catalog-products-form-prices-tpl'], function (SvCompPrices, tabTpl) {
    return {
        components: {
            'sv-comp-form-catalog-prices': SvCompPrices
        },
        template: tabTpl,
        props: ['form'],
        data: function () {
            return {
                view_mode: 'simple',
            };
        },
        watch: {
            'form.prices': {
                deep: true,
                handler: function (prices) {
                    this.$emit('event', 'tab-edited', 'prices');
                }
            }
        }
    }
});