define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-users-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    return {
        store: SvHlp.store,
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
        created: function () {
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