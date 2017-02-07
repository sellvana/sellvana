define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-users-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    return {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.grid],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            };
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