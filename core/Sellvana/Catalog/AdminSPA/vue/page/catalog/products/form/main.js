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
                return this.form.config;
            }
        },
        watch: {
            'form.product.product_name': function (value) { this.edited('product_name', value); },
            'form.product.url_key': function (value) { this.edited('url_key', value); },
            'form.product.product_sku': function (value) { this.edited('product_sku', value); },
            'form.product.short_description': function (value) { this.edited('short_description', value); },
            'form.product.description': function (value) { this.edited('description', value); },
            'form.product.categories': function (value) { this.edited('categories', value); },
            'form.product.is_hidden': function (value) { this.edited('is_hidden', value); },
            'form.product.is_featured': function (value) { this.edited('is_featured', value); },
            'form.product.is_popular': function (value) { this.edited('is_popular', value); }
        }
    }
});