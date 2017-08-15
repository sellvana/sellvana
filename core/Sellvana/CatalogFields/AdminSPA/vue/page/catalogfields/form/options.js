define(['lodash', 'vue', 'sv-hlp', 'text!sv-page-catalog-fields-form-options-tpl'], function (_, Vue, SvHlp, tabFieldOptionsTpl) {
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
            this.options = this.form.options;
        },
        props: ['form'],
        mixins: [SvHlp.mixins.common],
        template: tabFieldOptionsTpl,
        methods: {
            addNewOption: function () {
                this.options.push(_.clone(this.defaultOption))
            },
            deleteOption: function (option) {
                var idx = _.findIndex(this.options, function (o) {
                    return o === option;
                });
                if (idx !== -1) {
                    this.options.splice(idx, 1);
                }
            }
        }
    }
});