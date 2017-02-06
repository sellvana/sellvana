define(['lodash', 'vue', 'text!sv-comp-form-field-tpl'], function (_, Vue, fieldTpl) {

    var SvCompFormField = {
        template: fieldTpl,
        props: ['form', 'field', 'value'],
        data: function () {
            return {
                value_model: null
            }
        },
        computed: {
            field_config: function () {
                if (!this.form || !this.form.config || !this.form.config.fields) {
                    return {};
                }
                var i, l, f;
                for (i = 0, l = this.form.config.fields.length; i < l; i++) {
                    f = this.form.config.fields[i];
                    if (f.name === this.field) {
                        return f;
                    }
                }
                return {};
            },
            field_id: function () {
                var c = this.field_config;
                return c.id || ('form-' + c.model + '-' + c.tab + '-' + c.name);
            },
            field_type: function () {
                return this.field_config.type || 'text';
            },
            field_options: function () {
                var opts = this.field_config.options;
                if (_.isEmpty(opts)) {
                    return [];
                }
                if (_.isArrayLike(opts)) {
                    return this.field_config.options;
                }
                if (_.isObject(opts)) {
                    var options = [];
                    for (var i in opts) {
                        options.push({id: i, text: opts[i]});
                    }
                    return options;
                }
                return [];
            },
            field_errors: function () {
                if (this.form && this.form.errors && this.form.errors[this.field_config.model]) {
                    var errors = this.form.errors[this.field_config.model][this.field_config.name];
                    return !_.isEmpty(errors) ? errors : false;
                } else {
                    return false;
                }
            },
            i18n_enabled: function () {
                return SvAppData.modules.hasOwnProperty('Sellvana_MultiLanguage');
            }
        },
        methods: {
            parseValue: function (value) {
                if (this.field_config.multiple && (typeof value === 'string' || typeof value === 'number') ) {
                    value = [value];
                }
                this.value_model = value;
            },
            fieldConfig: function (key) {
                if ((typeof this.field_config[key]) === 'undefined') {
                    if (key.match(/^(multiple|required|readonly|disabled)$/)) {
                        return false;
                    }
                }
                return this.field_config[key];
            }
        },
        created: function () {
            this.parseValue(this.value);

        },
        watch: {
            value: function (value) {
                this.parseValue(value);
            },
            value_model: function (value) {
                this.$emit('input', value);
            }
        }
    };

    Vue.component('sv-comp-form-field', SvCompFormField);

    return SvCompFormField;
});