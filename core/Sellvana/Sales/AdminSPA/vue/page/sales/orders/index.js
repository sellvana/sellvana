define(['sv-app', 'sv-comp-grid', 'json!sv-page-sales-orders-grid-config'], function (SvApp, SvCompGrid, gridConfig) {
    return {
        store: SvApp.store,
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            }
        },
        components: {
            'sv-comp-grid': SvCompGrid
        },
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/sales/orders',
                label: 'Orders',
                breadcrumbs: [
                    {nav:'/sales', label:'Sales', icon_class:'fa fa-line-chart'},
                ]
            }});
        }
    };
});