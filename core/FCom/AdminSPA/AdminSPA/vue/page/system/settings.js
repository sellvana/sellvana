define(['sv-app'], function (SvApp) {
    return {
        store: SvApp.store,
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/settings',
                label: 'Settings',
                breadcrumbs: [
                    {nav:'/system', label: 'System', icon_class:'fa fa-cog'}
                ]
            }});
        }
    };
});