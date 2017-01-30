define(['sv-comp-grid', 'text!sv-page-catalog-categories-form-products-tpl', 'json!sv-page-catalog-categories-form-products-config'], function (SvCompGrid, tabCategoriesTpl, categoriesGridConfig) {

    return {
        props: {
            form: {
                type: Object
            }
        },
        data: function () {
            if (categoriesGridConfig.data_url && this.form.order && this.form.order.id) {
                categoriesGridConfig.data_url = categoriesGridConfig.data_url.supplant({id: this.form.order.id});
            }
            return {
                grid: {
                    config: categoriesGridConfig
                }
            }
        },
        template: tabCategoriesTpl,
        components: {
            'sv-comp-grid': SvCompGrid
        }
    };
});