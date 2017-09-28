define(['lodash', 'sv-mixin-form-tab'], function (_, SvMixinFormTab) {

    return {
        mixins: [SvMixinFormTab],
        props: ['settings', 'panel', 'site'],
        computed: {
            formFields: function () {
                return this.form && this.form.config && this.form.config.fields || [];
            }
        },
        methods: {
            processFieldEvent: function (event, args) {
                console.log(event, args);
            },
            fieldModel: function (field, root) {
                var path = (root || field.root).replace('/', '.'), model = _.get(this.settings.data, path, {});
                if (null === model) {
                    _.set(this.settings.data, path, {});
                    model = _.get(this.settings.data, path, {})
                }
                // console.log(this.settings.data, (root || field.root).replace(/\//, '.'), model);
                return model;
            }
        }
    }
});