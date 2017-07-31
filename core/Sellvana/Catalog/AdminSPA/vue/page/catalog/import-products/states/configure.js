define(['lodash', 'sv-mixin-common', 'text!sv-page-catalog-import-products-configure-tpl'], function (_, SvMixinCommon, tpl) {
    return {
        data: function () {
            return {
                isLoaded: false,
                isError: false,
                error: '',
                fileConfig: [
                    {label: 'Field Delimiter', field: 'delim', 'default': ','},
                    {label: 'Skip First Lines', field: 'skip_first', 'default': 1},
                    {label: 'Batch Size', field: 'batch_size', 'default': 100},
                    {label: 'Multi Value Delimiter', field: 'multivalue_separator', 'default': ';'},
                    {label: 'Nesting Level Delimiter', field: 'nesting_separator', 'default': '>'}
                ],
                config: {
                    'delim': ',',
                    'skip_first': 1,
                    'batch_size': 100,
                    'multivalue_separator': ';',
                    'nesting_separator': '>'
                }
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
            this.sendRequest('GET', 'import-products/config',
                {file: this.file.file_name},
                this.onConfig.bind(this),
                this.onConfigError.bind(this)
            );
        },
        methods: {
            onConfig: function (result) {
                this.isLoaded = true;
                _.assign(this.config, result);
                if (this.config.skip_first === true) {
                    this.config.skip_first = 1;
                }
                if (this.config.skip_first === false) {
                    this.config.skip_first = 0;
                }
            },
            onConfigError: function (error) {
                this.error = error;
                this.isError = true;
            }
        }
    }
});