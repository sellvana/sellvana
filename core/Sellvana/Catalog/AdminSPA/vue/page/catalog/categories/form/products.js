define(['sv-comp-grid', 'text!sv-page-catalog-products-form-categories-tpl', 'json!sv-page-catalog-products-form-categories-config'], function (SvCompGrid, tabProductsTpl, productsGridConfig) {

    return {
        props: {
            form: {
                type: Object
            }
        },
        data: function () {
            if (productsGridConfig.data_url && this.form.order && this.form.order.id) {
                productsGridConfig.data_url = productsGridConfig.data_url.supplant({id: this.form.order.id});
            }
            return {
                grid: {
                    config: productsGridConfig
                }
            }
        },
        template: tabProductsTpl,
        components: {
            'sv-comp-grid': SvCompGrid
        }
    };
});