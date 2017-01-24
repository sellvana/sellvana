define(['sv-hlp', 'text!sv-page-catalog-products-form-main-tpl'], function (SvHlp, tabMainTpl) {
    return {
        mixins: [SvHlp.mixins.formTab],
        template: tabMainTpl,
        props: ['form'],
        data: function () {
            return {
                dict: SvAppData
            }
        },
        computed: {
            formValidationConfig: function () {
                return {
                    fields: {
                        product_name: {tab: 'main', required: 1},
                        url_key: {tab: 'main'},
                        product_sku: {tab: 'main', required: 1},
                        short_description: {tab: 'main', required: 1},
                        description: {tab: 'main', required: 1},
                        categories: {tab: 'main'},
                        is_hidden: {tab: 'main'},
                        is_featured: {tab: 'main'},
                        is_popular: {tab: 'main'}
                    }
                };
            }
        },
        watch: {
            'form.product.product_name': function (value) { this.validate('product_name', value); },
            'form.product.url_key': function (value) { this.validate('url_key', value); },
            'form.product.product_sku': function (value) { this.validate('product_sku', value); },
            'form.product.short_description': function (value) { this.validate('short_description', value); },
            'form.product.description': function (value) { this.validate('description', value); },
            'form.product.categories': function (value) { this.validate('categories', value); },
            'form.product.is_hidden': function (value) { this.validate('is_hidden', value); },
            'form.product.is_featured': function (value) { this.validate('is_featured', value); },
            'form.product.is_popular': function (value) { this.validate('is_popular', value); }
        }
    }
});