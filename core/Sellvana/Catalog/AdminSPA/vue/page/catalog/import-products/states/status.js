define(['lodash', 'moment', 'sv-mixin-common', 'text!sv-page-catalog-import-products-status-tpl'], function (_, moment, SvMixinCommon, tpl) {
    function formatBytes(a, b) {
        if (0 === a || undefined === a) {
            return "0 Bytes";
        }
        var c = 1024,
            d = b || 2,
            e = ['Bits', "Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"],
            f = Math.floor(Math.log(a) / Math.log(c));
        return parseFloat((a / Math.pow(c, f)).toFixed(d)) + " " + e[f]
    }

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
            totalWarnings: function () {
                return this.config ? _.toInteger(this.config.rows_warning) : 0;
            },
            successful: function () {
                return this.config ? _.toInteger(this.config.rows_created) + _.toInteger(this.config.rows_updated) : 0;
            },
            estimatedRunTime: function () {
                if (!this.config.run_time) {
                    return 0;
                }
                var runtime = _.round(this.crunchRate * this.config.rows_total, 2);
                return moment.duration(runtime, 'seconds').humanize();
            },
            runTime: function () {
                if (!this.config.run_time) {
                    return 0;
                }
                return moment.duration(this.config.run_time, 'seconds').humanize();
            },
            estimatedFinish: function () {
                if (!this.config.start_time) {
                    return 0;
                }
                var runtime = moment.unix(this.config.start_time + _.round(this.crunchRate * this.config.rows_total, 2));
                return runtime.format("YYYY MMM DD, HH:mm:ss");
            },
            peakMemoryUsage: function () {
                return formatBytes(this.config.memory_peak_usage);
            },
            memoryUsage: function () {
                return formatBytes(this.config.memory_usage);
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