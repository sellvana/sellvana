define(['sv-comp-grid', 'text!sv-page-catalog-inventory-form-products-tpl', 'json!sv-page-catalog-inventory-form-products-config'], function (SvCompGrid, tabProductsTpl, productsGridConfig) {

    return {
        props: {
            form: {
                type: Object
            }
        },
        data: function () {
            if (productsGridConfig.data_url && this.form.inventory && this.form.inventory.inventory_sku) {
                productsGridConfig.data_url = productsGridConfig.data_url.supplant({inventory_sku: this.form.inventory.inventory_sku});
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