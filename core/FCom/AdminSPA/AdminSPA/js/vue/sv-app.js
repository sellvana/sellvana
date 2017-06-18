define(['vue', 'sv-hlp', 'sv-comp-header', 'sv-comp-header-breadcrumbs', 'sv-comp-messages', 'sv-comp-nav',
        'sv-comp-actions', 'sv-comp-tabs',
        'text!sv-comp-header-tpl', 'text!sv-comp-nav-tpl'],
    function (Vue, SvHlp, SvCompHeader, SvCompHeaderBreadcrumbs, SvCompMessages, SvCompNav) {

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
            'sv-comp-header': SvCompHeader,
            'sv-comp-messages': SvCompMessages,
            'sv-comp-nav': SvCompNav,
            'sv-comp-header-breadcrumbs': SvCompHeaderBreadcrumbs,
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