define(['lodash', 'sv-mixin-common', 'text!sv-page-catalog-import-products-configure-tpl'], function (_, SvMixinCommon, tpl) {
    var configRoute = 'import-products/config';
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
                    defaults: {},
                    delim: ',',
                    skip_first: 1,
                    batch_size: 100,
                    multivalue_separator: ';',
                    nesting_separator: '>',
                    field_options: {},
                    field_data: {},
                    first_row: [],
                    columns: []
                },
                select2params: {
                    allowClear: true
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
            this.sendRequest('GET', configRoute,
                {file: this.file.file_name},
                this.onConfig.bind(this),
                this.onConfigError.bind(this)
            );
            this.$on('import-start', function () {
                // save config and signal start import
                this.sendRequest('POST', configRoute, {
                    config: this.config
                }).done(function (result) {
                    this.$emit('config-saved');
                }.bind(this))
            }.bind(this))
        },
        computed: {
            select2options: function () {
                var options = [];
                if (this.config.field_options) {
                    options = this.config.field_options;
                }
                return options;
            }
        },
        methods: {
            setColumn: function (col, ev) {
                this.config.columns[col] = ev.target.value;
            },
            onConfig: function (result) {
                this.isLoaded = true;
                console.log(result);
                if (!_.isUndefined(result.defaults)) {
                    result.defaults = {};
                }

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