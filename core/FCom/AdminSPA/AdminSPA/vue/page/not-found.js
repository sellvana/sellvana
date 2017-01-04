define(['sv-hlp'], function (SvHlp) {
    return {
        store: SvHlp.store,
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