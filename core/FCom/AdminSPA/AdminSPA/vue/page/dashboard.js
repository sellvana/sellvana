define(['sv-hlp'], function (SvHlp) {

    var SvPageDashboard = {
        mixins: [SvHlp.mixins.common],
        store: SvHlp.store,
		mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/',
                label: 'Dashboard',
				icon_link: '<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="img/icons.svg#icon-dashboard"></use>'
            }});
            this.sendRequest('GET', 'dashboard', {}, function (response) {
                response
            });
        },
        methods: {
            sortingUpdate: function (ev) {
                console.log(ev);
            }
        }
    };

    return SvPageDashboard;
});