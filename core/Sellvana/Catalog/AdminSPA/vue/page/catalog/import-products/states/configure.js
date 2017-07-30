define(['sv-mixin-common', 'text!sv-page-catalog-import-products-configure-tpl'], function (SvMixinCommon, tpl) {
    return {
        data: function () {
            return {
                config: {}
            }
        },
        mixins: [SvMixinCommon],
        props: {
            file: {
                type: Object,
                default: function () {
                    return {};
                }
            }
        },
        template: tpl,
        mounted: function () {
            this.sendRequest('GET', 'import-products/config', {file: this.file.file_name}, this.onConfig.bind(this), this.onConfigError.bind(this));
        },
        methods: {
            onConfig: function (result) {
                this.config = result;
            },
            onConfigError: function (error) {
                console.log('error', error);
            }
        }
    }
});