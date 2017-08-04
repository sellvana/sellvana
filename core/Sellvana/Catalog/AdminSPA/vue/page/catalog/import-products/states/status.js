define(['lodash', 'sv-mixin-common', 'text!sv-page-catalog-import-products-status-tpl'], function (_, SvMixinCommon, tpl) {
    return {
        mixins: [SvMixinCommon],
        props: {
            config: {
                type: Object,
                required: true
            }
        },
        computed: {
            crunchRate: function () {
                return this.config.run_time ? _.round(this.config.rows_processed / this.config.run_time, 2) : 0
            },
            totalErrors: function () {
                return this.config.errors ? this.config.errors.length : 0;
            },
            successful: function () {
                return this.config ? _.toInteger(this.config.rows_created) + _.toInteger(this.config.rows_updated) : 0;
            }
        },
        filters: {
            int: function (val) {
                var number = parseInt(val, 10);
                return isNaN(number) ? 0 : number;
            }
        },
        data: function () {
            return {
                c: {}
            }
        },
        mounted: function () {
            this.c = this.config;
        },
        template: tpl
    }
});