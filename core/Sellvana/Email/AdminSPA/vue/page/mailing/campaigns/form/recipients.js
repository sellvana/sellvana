define(['sv-mixin-form-tab', 'text!sv-page-default-form-tab-grid-tpl'], function (SvMixinFormTab, tpl) {
    var Component = {
        mixins: [SvMixinFormTab],
        template: tpl,
        props: {
            form: {
                type: Object
            }
        },
        data: function () {
            return {
                grid: this.form.recipients_grid
            }
        },
        methods: {
            doPanelAction: function (args) {
                switch (args.name) {
                    case 'import_from_list':
                        this.importFromList();
                }
            },
            importFromList: function () {
                this.sendRequest('POST', 'mailing/campaigns')
            }
        }
    };

    return Component;
});