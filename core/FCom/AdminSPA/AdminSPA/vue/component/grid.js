define(['vue', 'vue-strap', 'sv-app',
        'text!sv-comp-grid-tpl', 'text!sv-comp-grid-header-row-tpl', 'text!sv-comp-grid-data-row-tpl',
        'text!sv-comp-grid-pager-list-tpl', 'text!sv-comp-grid-pager-select-tpl', 'text!sv-comp-grid-panel-tpl',
        'text!sv-comp-grid-panel-columns-tpl', 'text!sv-comp-grid-panel-filters-tpl', 'text!sv-comp-grid-panel-export-tpl',
        'text!sv-comp-grid-bulk-actions-tpl'
    ],
    function(Vue, VueStrap, SvApp, gridTpl, gridHeaderRowTpl, gridDataRowTpl, gridPagerListTpl, gridPagerSelectTpl, gridPanelTpl,
             gridPanelColumnsTpl, gridPanelFiltersTpl, gridPanelExportTpl, gridBulkActionsTpl
    ) {

        var GridHeaderRow = {
            template: gridHeaderRowTpl
        };
        var GridDataRow = {
            template: gridDataRowTpl
        };

        var GridPagerList = {
            template: gridPagerListTpl
        };

        var GridPagerSelect = {
            template: gridPagerSelectTpl
        };

        var GridPanelColumns = {
            template: gridPanelColumnsTpl
        };

        var GridPanelFilters = {
            template: gridPanelFiltersTpl
        };

        var GridPanelExport = {
            template: gridPanelExportTpl
        };

        var GridBulkActions = {
            template: gridBulkActionsTpl,
            components: {
                'v-select': VueStrap.select
            }
        };

        var GridPanel = {
            components: {
                'sv-comp-grid-pager-list': GridPagerList,
                'sv-comp-grid-panel-columns': GridPanelColumns,
                'sv-comp-grid-panel-filters': GridPanelFilters,
                'sv-comp-grid-panel-export': GridPanelExport,
                'sv-comp-grid-bulk-actions': GridBulkActions
            },
            template: gridPanelTpl
        };

        return {
            data: function() {
                return {
                    ui: {

                    },
                    config: {
                        columns: [
                            {type:'select-checkbox'},
                            {type:'actions'},
                            {field:'id', label:'ID'},
                            {field:'state_overall', label:'Overall State', options:[
                                {value:'pending', label:'Pending'},
                                {value:'processing', label:'Processing'},
                                {value:'shipped', label:'Shipped'}
                            ]}
                        ],
                        filters: [
                            {field:'id'},
                            {field:'state_overall', type:'multiselect'}
                        ],
                        export: {
                            format_options: [
                                 {value:'csv', label:'CSV'}
                            ]
                        },
                        pager: {
                            pagesize_options: [5, 10, 20, 50, 100]
                        }
                    },
                    rows: [
                        {id:1, state_overall:'processing'},
                        {id:2, state_overall:'pending'},
                        {id:3, state_overall:'shipped'}
                    ]
                }
            },
            methods: {
                svAsset: SvApp.methods.assetUrl
            },
            components: {
                'sv-comp-grid-header-row': GridHeaderRow,
                'sv-comp-grid-data-row': GridDataRow,
                'sv-comp-grid-panel': GridPanel,
                'sv-comp-grid-pager-select': GridPagerSelect,
            },
            template: gridTpl
        };
});