define(['sv-mixin-form-tab', 'text!sv-page-default-form-tab-grid-tpl'], function (SvMixinFormTab, tpl) {
    var formTabGridMixin = {
        mixins: [SvMixinFormTab],
        template: tpl,
        methods: {
            onEvent: function (eventType, args) {
                switch (eventType) {
                    case 'panel-action':
                        this.doPanelAction(args);
                        break;

                    case 'bulk-action':
                        this.doBulkAction(args);
                        break;

                    case 'row-action':
                        this.doRowAction(args);
                        break;

                    default:
                        this.emitEvent(eventType, args);
                }
            },
            doPanelAction: function (act) {
                console.log(act);
            },
            doBulkAction: function (act) {
                console.log(act);
            },
            doRowAction: function (act) {
                console.log(act);
            }
        }
    };

    return formTabGridMixin;
})