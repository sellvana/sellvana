define(['sv-mixin-form-tab-grid'], function (SvMixinFormTabGrid) {
    var Component = {
        mixins: [SvMixinFormTabGrid],
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