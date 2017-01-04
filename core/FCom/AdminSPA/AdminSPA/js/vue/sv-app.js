define(['vue', 'sv-hlp', 'sv-comp-header', 'text!sv-comp-header-tpl', 'sv-comp-menu', 'text!sv-comp-menu-tpl'],
    function (Vue, SvHlp, SvHeader, SvHeaderTpl, SvMenu, menuTpl) {

    var SvApp = {
        el: '#sv-app',
        mixins: [SvHlp.mixins.common],
        data: function () {
            return {
                ui: this.$store.state.ui,
                isLoaded: true
            };
        },
        computed: {
            isLoggedIn: function () {
                return this.$store && this.$store.state && this.$store.state.user && this.$store.state.user.id;
            }
        },
        methods: {
            pageClick: function() {
                this.ddToggle(false);
                this.$store.commit('pageClick');
            }
        },
        components: {
            'sv-comp-header': SvHeader,
            'sv-comp-menu': SvMenu
        },
        created: function () {
            if (!this.isLoggedIn) {
                this.$router.push('/login');
            }

        },
        router: SvHlp.router,
        store: SvHlp.store
    };

    new Vue(SvApp);

    return SvApp;
});