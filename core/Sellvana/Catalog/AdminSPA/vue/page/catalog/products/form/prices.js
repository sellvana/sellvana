define(['sv-comp-form-catalog-prices', 'text!sv-page-catalog-products-form-prices-tpl'], function (SvCompPrices, tabTpl) {
    return {
        components: {
            'sv-comp-form-catalog-prices': SvCompPrices
        },
        template: tabTpl,
        props: ['form'],
        watch: {
            'form.prices': {
                deep: true,
                handler: function (prices) {
                    this.$emit('event', 'tab_edited', 'prices');
                }
            }
        }
    }
});