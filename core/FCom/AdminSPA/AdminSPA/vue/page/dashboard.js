define(['sv-app', 'sv-comp-grid'], function (SvApp, SvGrid) {
    return {
        store: SvApp.store,
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/',
                label: 'Dashboard',
                icon_class: 'fa fa-tachometer',
                breadcrumbs: [
                ]
            }});
        }
    };
});