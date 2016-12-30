define(['vue', 'sv-app', 'text!sv-comp-menu-tpl'], function(Vue, SvApp, menuTpl) {
    return {
        store: SvApp.store,
        data: function () {
            return {
                ui: this.$store.state.ui,
                curPage: this.$store.state.curPage,
                navTreeOpen: {}
            };
        },
        computed: {
            navOpen: function () {
                return function (path) {
                    return this.navTreeOpen[path];
                }
            },
            inBreadcrumbs: function () {
                return function (node) {
                    var curPage = this.$store.state.curPage;
                    if (curPage.link === node.link || curPage.nav === node.path) {
                        return true;
                    }
                    for (var i = 0; i < curPage.breadcrumbs.length; i++) {
                        if (curPage.breadcrumbs[i].link === node.link || curPage.breadcrumbs[i].nav === node.path) {
                            return true;
                        }
                    }
                    return false;
                }
            },
            mainNavOpen: function () {
                return this.$store.state.ui.mainNavOpen;
            }
        },
        methods: {
            navToggle: function (path) {
                Vue.set(this.navTreeOpen, path, !this.navTreeOpen[path]);
                console.log(path, this.navTreeOpen);
            }
        },
        template: menuTpl,
        watch: {
            mainNavOpen: function (a) {
                if (!a) {
                    this.navTreeOpen = {};
                }
            }
        }
    }
})