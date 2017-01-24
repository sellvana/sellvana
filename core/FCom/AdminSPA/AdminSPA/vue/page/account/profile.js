define(['sv-hlp'], function (SvHlp) {
    return {
        store: SvHlp.store,
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/profile',
                label: 'Account Profile',
                breadcrumbs: [
                ]
            }});
        }
    };
});