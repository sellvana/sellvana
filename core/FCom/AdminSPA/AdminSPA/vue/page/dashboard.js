define(['sv-mixin-common', 'sv-page-dashboard-widget', 'text!sv-page-dashboard-widget-tpl'], function (SvMixinCommon, SvPageDashboardWidget) {

    var SvPageDashboard = {
        mixins: [SvMixinCommon],
        data: function () {
            return {
                widgets: []
            }
        },
		created: function () {
            var vm = this;
            this.$store.commit('setData', {curPage: {link: '/', label: 'Dashboard'}});

            this.sendRequest('GET', 'dashboard', {}, function (response) {
                if (!response.widgets) {
                    return;
                }
                var i, l, w, reqs = [];
                for (i = 0, l = response.widgets.length; i < l; i++) {
                    w = response.widgets[i];
                    if (w.template) {
                        reqs.push('text!' + w.template);
                    }
                }
                require(reqs, function () {
                    vm.widgets = response.widgets;
                })
            });
        },
        methods: {
            sortingUpdate: function (ev) {
                console.log(ev);
            }
        },
        components: {
            'sv-page-dashboard-widget': SvPageDashboardWidget
        }
    };

    return SvPageDashboard;
});