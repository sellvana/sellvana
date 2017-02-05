define(['lodash', 'sv-hlp'], function (_, SvHlp) {

    return {
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
            fieldModel: function (field, root) {
                return _.get(this.settings.data, (root || field.root).replace('/', '.'), {});
            },
            processFieldEvent: function (event, args) {
                console.log(event, args);
            },
            showCond: function (f) {
                if (!f.if) {
                    return true;
                }
                var vm = this, cond = f.if, result;

                cond.replace(/\{(([a-z0-9_/]+)\/)?([a-z0-9_]+)\}/g, function (_, _, root, field) {
                    var result = vm.fieldModel(f, root)[field];
// console.log(result);
                    return result;
                });

                cond = cond.replace(/\{(([a-z0-9_/]+)\/)?([a-z0-9_]+)\}/g, "this.fieldModel(f, '$2').$3");
                result = eval(cond);
// console.log(cond, result);

                return result;
            }
        }
    }
});