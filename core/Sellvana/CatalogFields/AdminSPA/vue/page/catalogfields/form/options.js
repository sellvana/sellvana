define(['lodash', 'sv-hlp', 'text!sv-page-catalog-fields-form-options-tpl'], function (_, SvHlp, tabFieldOptionsTpl) {
    return {
        data: function () {
            return {
                options: [],
                defaultOption: {
                    label: '',
                    swatch_info: ''
                }
            }
        },
        mounted: function() {
            this.fetchData();
        },
        mixins: [SvHlp.mixins.common],
        template: tabFieldOptionsTpl,
        methods: {
            addNewOption: function () {
                this.options.push(_.clone(this.defaultOption))
            },
            deleteOption: function (option) {
                this.options = _.reject(this.options, function (o) {
                    return o === option;
                });
            },
            fetchData: function () {
                var vm = this;
                if (this.$route.query) {
                    this.sendRequest('GET', 'catalogfields/options/data', this.$route.query, function (response) {
                        if(_.isArray(response)) {
                            _.each(response, function (op) {
                                vm.options.push(op);
                            });
                        }
                    });
                }
            }
        }
    }
});