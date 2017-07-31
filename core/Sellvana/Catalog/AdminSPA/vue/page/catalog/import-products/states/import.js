define(['sv-mixin-common',
    'sv-page-catalog-import-products-status',
    'text!sv-page-catalog-import-products-import-tpl'], function (SvMixinCommon, SvCsvImpStatus, tpl) {
    var baseUrl = 'import-products';
    return {
        mixins: [SvMixinCommon],
        data: function () {
            return {
                config: {},
                start: true
            };
        },
        mounted: function () {
            if (this.start) {
                this.startImport();
            }
            this.start = false;
        },
        methods: {
            startImport: function () {
                // todo
                // start import on admin, upon completion emit import:complete event
                var url = baseUrl + '/start';

                this.sendRequest('POST', url, {})
                    .done(function (result) {
                        if (result) {
                            this.config = result;
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
                            this.config = result;
                        }
                    })
                    .fail(function (error) {
                        console.error('ADMIN-SPA', error);
                    });
            },

            fetchStatus: function () {
                // fetch status from admin and update config
                var url = baseUrl + '/status';
                this.sendRequest('GET', url, {})
                    .done(function (result) {
                        if (result) {
                            this.config = result;
                        }
                    })
                    .fail(function (error) {
                        console.error('ADMIN-SPA', error);
                    });
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