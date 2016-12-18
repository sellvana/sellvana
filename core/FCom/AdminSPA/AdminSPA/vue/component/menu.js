define(['vue', 'sv-app', 'text!sv-comp-menu-tpl'], function(Vue, SvApp, menuTpl) {
    return {
        store: SvApp.store,
        data: function () {
            return {
                ui: this.$store.state.ui,
                navCurrent: this.$store.state.navCurrent,
                navTreeOpen: {}
            };
        },
        computed: {
            navOpen: function () {
                return function (path) {
                    return this.navTreeOpen[path];
                }
            }
        },
        methods: {
            navToggle: function (path) {
                Vue.set(this.navTreeOpen, path, !this.navTreeOpen[path]);
                console.log(path, this.navTreeOpen);
            }
        },
        template: menuTpl,
        store: SvApp.store
    }
})