define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-sales-orders-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    return {
        mixins: [SvHlp.mixins.grid],
        store: SvHlp.store,
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