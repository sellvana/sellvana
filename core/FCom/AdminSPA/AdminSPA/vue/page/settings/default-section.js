define(['lodash', 'sv-mixin-form-tab'], function (_, SvMixinFormTab) {

    return {
        mixins: [SvMixinFormTab],
        props: ['settings', 'panel', 'site'],
        computed: {
            form: function () {
                return this.settings.config.forms[this.panel.path];
            },
            formFields: function () {
                return this.form && this.form.config.fields || [];
            }
        },
        methods: {
            processFieldEvent: function (event, args) {
                console.log(event, args);
            },
            fieldModel: function (field, root) {
                return _.get(this.settings.data, (root || field.root).replace('/', '.'), {});
            }
        }
    }
});