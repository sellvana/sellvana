define(['sv-mixin-grid', 'sv-comp-grid', 'json!sv-page-roles-grid-config'], function (SvMixinGrid, SvCompGrid, gridConfig) {
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
                link: '/roles',
                label: 'Roles',
                breadcrumbs: [
                    {nav:'/system', label: 'System', icon_class:'fa fa-cog'}
                ]
            }});
        }
    };
});