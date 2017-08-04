define(['sv-mixin-common',
    'sv-page-catalog-import-products-status',
    'text!sv-page-catalog-import-products-import-tpl'], function (SvMixinCommon, SvCsvImpStatus, tpl) {
    var baseUrl = 'import-products';
    return {
        mixins: [SvMixinCommon],
        data: function () {
            return {
                start: true,
                c: {}
            };
        },
        props: {
            baseUrl: {
                type: String,
                required: true
            },
            config: {
                type: Object,
                required: true
            }
        },
        mounted: function () {
            this.c = this.config;
            if (this.start) {
                this.startImport();
            }
            this.start = false;
        },
        methods: {
            startImport: function () {
                // start import on admin, upon completion emit import:complete event
                var url = baseUrl + '/start';
                var self = this;
                this.sendRequest('POST', url, {})
                    .done(function (result) {
                        console.log(result);

                        if (result) {
                            self.c = result;
                        }

                        if ( result.status === 'stopped' || result.status === 'done') {
                            self.$emit('import-complete')
                        }
                    })
                    .fail(function (error) {
                        console.error('ADMIN-SPA', error);
                    });
                setTimeout(this.fetchStatus, 2000);
            },

            stopImport: function () {
                // stop current import, emit import:stopped event
                var url = baseUrl + '/stop';
                this.sendRequest('POST', url, {})
                    .done(function (result) {
                        if (result) {
                            this.c = result;
                        }
                    })
                    .fail(function (error) {
                        console.error('ADMIN-SPA', error);
                    });
            },

            fetchStatus: function () {
                console.log('fetching status');

                this.$emit('status');
            }
        },
        computed: {
            complete: function () {
                if (!this.config.rows_processed) {
                    return 0;
                }
                return this.config.rows_processed / this.config.rows_total * 100
            }
        },
        components: {
            'sv-csv-imp-status': SvCsvImpStatus
        },
        template: tpl
    }
});