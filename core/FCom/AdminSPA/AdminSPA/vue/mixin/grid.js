define(['lodash', 'vue', 'sv-comp-grid', 'text!sv-page-default-grid-tpl'],
    function (_, Vue, SvCompGrid, svPageDefaultGridTpl) {

    var gridMixin = {
        template: svPageDefaultGridTpl,
        methods: {
            onEvent: function (type, args) {
                if (args.link) {
                    window.location = '#' + args.link;
                    return;
                }
                switch (type) {
                    case 'grid-action':
                        this.doGridAction(args);
                        break;

                    case 'bulk-action':
                        this.doBulkAction(args);
                        break;

                    case 'popup-action':
                        this.doPopupAction(args);
                        break;

                    case 'panel-action':
                        this.doPanelAction(args);
                        break;

                    default:
                        console.log(type, args);
                        this.emitEvent(type, args);
                }
            },
            doDefaultBulkAction: function (act) {
                switch (act.name) {
                    case 'bulk_update':
                    case 'bulk_delete':
                        var vm = this,
                            data = this.grid.popup.form ? _.cloneDeep(this.grid.popup.form) : {},
                            ids = Object.keys(this.grid.rows_selected);
                        delete data.config;
                        var postData = {do: act.name, ids: ids, data: data};
                        this.sendRequest('POST', this.grid.config.data_url, postData, function (response) {
                            console.log(response);
                            vm.$set(vm.grid, 'popup', null);
                            vm.$set(vm.grid, 'fetch_data_flag', true);
                        });
                        break;

                    case 'cancel':
                        this.grid.popup = null;
                        break;
                }
            },
            doBulkAction: function (act) {
                this.doDefaultBulkAction(act);
                console.log(act);
            },
            doPanelAction: function (act) {
                console.log(act);
            },
            doPopupAction: function (act) {
                console.log(act);
            }
        },
        components: {
            'sv-comp-grid': SvCompGrid
        }
    };

    return gridMixin;
});