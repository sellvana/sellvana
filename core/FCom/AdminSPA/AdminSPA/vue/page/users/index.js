define(['sv-mixin-grid', 'json!sv-page-users-grid-config'], function (SvMixinGrid, gridConfig) {
    return {
        mixins: [SvMixinGrid],
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