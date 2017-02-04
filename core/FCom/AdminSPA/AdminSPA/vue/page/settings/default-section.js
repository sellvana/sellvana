define(['lodash', 'sv-hlp'], function (_, SvHlp) {
    console.log('TEST');
    return {
        props: ['settings', 'panel'],
        computed: {
            form: function () {
                return this.settings.config.sections[this.panel.path];
            },
            formFields: function () {
                return this.form && this.form.config.fields || [];
            }
        },
        methods: {
            fieldModel: function (field) {
                return _.get(this.settings.data, field.root.replace('/', '.'));
            },
            processFieldEvent: function (event, args) {
                console.log(event, args);
            }
        }
    }
});