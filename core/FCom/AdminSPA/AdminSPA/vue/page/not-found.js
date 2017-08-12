define([], function () {
    return {
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