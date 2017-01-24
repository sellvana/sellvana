define(['sv-hlp'], function (SvHlp) {

    return {
        store: SvHlp.store,
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/',
                label: 'Dashboard',
                icon_class: 'fa fa-tachometer',
                breadcrumbs: [
                ]
            }});
        },
        methods: {
            sortingUpdate: function (ev) {
                console.log(ev);
            }
        }
    };
});