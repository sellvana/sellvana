define(['sv-hlp', 'text!sv-page-vendors-form-main-tpl'], function (SvHlp, tabMainTpl) {
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
                        vendor_name: {tab: 'main', required: 1},
                        url_key: {tab: 'main'},
                        short_description: {tab: 'main', required: 1},
                    }
                };
            }
        },
        watch: {
            'form.vendor.product_name': function (value) { this.validate('product_name', value); },
            'form.vendor.url_key': function (value) { this.validate('url_key', value); },
            'form.vendor.short_description': function (value) { this.validate('short_description', value); }
        }
    }
});