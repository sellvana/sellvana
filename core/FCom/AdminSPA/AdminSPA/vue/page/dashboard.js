define(['sv-hlp'], function (SvHlp) {

    return {
        store: SvHlp.store,
		mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/',
                label: 'Dashboard',
              /*  icon_class: 'fa fa-tachometer', */
				icon_link: '<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="img/icons.svg#icon-dashboard"></use>',
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