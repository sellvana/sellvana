define(['sv-app'], function (SvApp) {
    return {
        store: SvApp.store,
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/not-found',
                label: 'Page Not Found',
                breadcrumbs: [
                ]
            }});
        }
    };
});