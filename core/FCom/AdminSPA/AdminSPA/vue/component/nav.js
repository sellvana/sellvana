define(['vue', 'text!sv-comp-nav-tpl'], function(Vue, navTpl) {
    var SvCompNav = {
        template: navTpl,
        data: function () {
            return {
                ui: this.$store.state.ui,
                curPage: this.$store.state.curPage,
                navTreeOpen: {}
            };
        },
        computed: {
            mainNavOpen: function () {
                return this.$store.state.ui.mainNavOpen;
            },
            getUser: function () {
                return this.$store.state.user;
            }
        },
        methods: {
            navOpen: function  (path) {
                return this.navTreeOpen[path];
            },
            inBreadcrumbs: function (node) {
                var curPage = this.$store.state.curPage;
                if (curPage.link === node.link || curPage.nav === node.path) {
                    return true;
                }
                if (curPage.breadcrumbs) {
                    for (var i = 0; i < curPage.breadcrumbs.length; i++) {
                        if (curPage.breadcrumbs[i].link === node.link || curPage.breadcrumbs[i].nav === node.path) {
                            return true;
                        }
                    }
                }
                return false;
            },
            navToggle: function (path) {
                this.$set(this.navTreeOpen, path, !this.navTreeOpen[path]);
            },
            clickRouterLink: function(node) {
                if (this.$store.state.ui.windowWidth <= 1024) {
                    this.$store.commit('mainNavToggle');
                }
            }
        },
        watch: {
            mainNavOpen: function (a) {
                if (!a) {
                    this.navTreeOpen = {};
                }
            }
        }

    };

    Vue.component('sv-comp-nav', SvCompNav);

    return SvCompNav;
});