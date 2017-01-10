define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-customers-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    return {
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
                link: '/customers/customers',
                label: 'Customers',
                breadcrumbs: [
                    {nav:'/customers', label:'Customers', icon_class:'fa fa-user'},
                ]
            }});
        }
    };
});