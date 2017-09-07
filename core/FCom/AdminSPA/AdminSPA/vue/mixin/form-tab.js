define(['lodash', 'vue', 'sv-app-data', 'sv-comp-form-field', 'text!sv-page-default-form-tab-tpl'],
    function (_, Vue, SvAppData, SvCompFormField, svPageDefaultFormTabTpl) {

    var formTabMixin = {
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
            i18n_enabled: function () {
                return SvAppData.modules.hasOwnProperty('Sellvana_MultiLanguage');
            }
        },
        methods: {
            fieldClass: function (field) {
                return {};
            },
            edited: function (field, value) {
                var config = this.form.config;
                if (!config.fields || !config.fields[field]) {
                    return;
                }
                this.setTabFlag('edited', true);
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
                        this.$set(this.form.i18n, args.field.name, args.translations);
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
                if (_.isArray(cond)) {
                    var i, matches, model, field;
                    for (i = 0; i < cond.length; i++) {
                        matches = true;
                        for (model in cond[i]) {
                            for (field in cond[i][model]) {
                                if (this.form[model][field] !== cond[i][model][field]) {
                                    matches = false;
                                }
                            }
                        }
                        if (matches) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    cond = cond.replace(/\{(([a-z0-9_.]+)[.])?([a-z0-9_]+)\}/g, "this.fieldModel(f, '$2').$3");
                    result = eval(cond);
                }

                return result;
            },

            setTabFlag: function (flag, value) {
                for (var i = 0, l = this.form.config.tabs.length, tab; tab = this.form.config.tabs[i], i < l; i++) {
                    if (tab.name === this.tab) {
                        this.$set(tab, flag, value);
                        break;
                    }
                }
            },

            removeSelectedLocalRows: function () {
                var rows = [];
                for (var i = 0, l = this.grid.rows.length, r; r = this.grid.rows[i], i < l; i++) {
                    if (!this.grid.rows_selected[r.id]) {
                        rows.push(r);
                    }
                }
                this.$set(this.grid, 'rows', rows);
                this.$set(this.grid, 'rows_selected', {});
            },
            removeCurrentLocalRow: function (idValue, idField) {
                var rows = [];
                idField = idField || 'id';
                for (var i = 0, l = this.grid.rows.length, r; r = this.grid.rows[i], i < l; i++) {
                    if (r[idField] !== idValue) {
                        rows.push(r);
                    }
                }
                this.$set(this.grid, 'rows', rows);
            }
        }
    };

    return formTabMixin;
});