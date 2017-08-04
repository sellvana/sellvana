define(['sv-mixin-form-tab', 'text!sv-page-default-form-tab-grid-tpl'], function (SvMixinFormTab, tpl) {
    var formTabGridMixin = {
        mixins: [SvMixinFormTab],
        template: tpl,
        methods: {
            onEvent: function (eventType, args) {
                switch (eventType) {
                    case 'panel-action':
                        this.doPanelAction(args);

                    case 'bulk-action':
                        this.doBulkAction(args);

                    case 'row-action':
                        this.doRowAction(args);

                    default:
                        this.emitEvent(eventType, args);
                }
            }
        }
    };

    return formTabGridMixin;
})