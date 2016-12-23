define(['sv-app', 'sv-comp-grid', 'json!sv-page-system-users-grid-config'], function (SvApp, SvCompGrid, gridConfig) {
    return {
        store: SvApp.store,
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            };
        },
        components: {
            'sv-comp-grid': SvCompGrid
        },
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/users',
                label: 'Users',
                breadcrumbs: [
                    {nav:'/system', label: 'System', icon_class:'fa fa-cog'}
                ]
            }});
        }
    };
});