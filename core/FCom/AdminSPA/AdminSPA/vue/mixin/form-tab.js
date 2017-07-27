define(['lodash', 'vue', 'sv-app-data', 'sv-mixin-common', 'sv-comp-form-field', 'text!sv-page-default-form-tab-tpl'],
    function (_, Vue, SvAppData, SvMixinCommon, SvCompFormField, svPageDefaultFormTabTpl) {

    var formTabMixin = {
        mixins: [SvMixinCommon],
        template: svPageDefaultFormTabTpl,
        components: {
            'sv-comp-form-field': SvCompFormField
        },
        props: ['form', 'tab'],
        data: function () {
            return {
                i18n_field: false
            }
        },
        computed: {
            fieldClass: function () {
                var vm = this;
                return function (field) {
                    return {};
                }
            },
            i18n_enabled: function () {
                return SvAppData.modules.hasOwnProperty('Sellvana_MultiLanguage');
            }
        },
        methods: {
            edited: function (field, value) {
                var config = this.form.config;
                if (!config.fields || !config.fields[field]) {
                    return;
                }
                var tab = config.fields[field].tab;
                for (var i = 0, l = this.form.config.tabs.length; i < l; i++) {
                    if (this.form.config.tabs[i].name === tab) {
                        Vue.set(this.form.config.tabs[i], 'edited', true);
                        break;
                    }
                }
            },
            processFieldEvent: function (type, args) {
                switch (type) {
                    case 'toggle_i18n':
                        this.toggleTranslations(args);
                        break;
                }
            },
            toggleTranslations: function (field) {
                if (this.i18n_field && this.i18n_field.name === name) {
                    this.i18n_field = false;
                } else {
                    this.i18n_field = field;
                }
            },
            processTranslationsEvent: function (type, args) {
                switch (type) {
                    case 'update':
                        // args: field, translations
                        Vue.set(this.form.i18n, args.field.name, args.translations);
                        break;

                    case 'close':
                        this.i18n_field = false;
                        break;
                }
            },
            fieldModel: function (field, root) {
                var model;
                if (root) {
                    model = _.get(this.form, (root || field.root).replace('/', '.'), {});
                } else {
                    model = this.form[field.model];
                }
                return model;
            },
            formFieldShowCond: function (f) {
                if (!f.if) {
                    return true;
                }
                var vm = this, cond = f.if, result;

                // result = cond.replace(/\{(([a-z0-9_/]+)\/)?([a-z0-9_]+)\}/g, function (_, _, root, field) {
                //     return vm.fieldModel(f, root)[field];
                // });

                cond = cond.replace(/\{(([a-z0-9_/]+)\/)?([a-z0-9_]+)\}/g, "this.fieldModel(f, '$2').$3");
                result = eval(cond);

                return result;
            },
            onEvent: function (eventType, args) {
                switch (eventType) {
                    case 'panel-action':
                        this.doPanelAction(args);

                    default:
                        this.$emit('event', eventType, args);
                }
            }
        }
    };

    return formTabMixin;
});