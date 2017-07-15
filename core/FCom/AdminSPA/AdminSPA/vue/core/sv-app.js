define(['vue', 'sv-mixin-common', 'sv-mixin-common', 'sv-mixin-grid', 'sv-mixin-form', 'sv-mixin-form-tab',
        'sv-comp-header', 'sv-comp-header-breadcrumbs', 'sv-comp-messages', 'sv-comp-nav',
        'sv-hlp', 'sv-comp-actions', 'sv-comp-tabs',
        'text!sv-comp-header-tpl', 'text!sv-comp-nav-tpl'],
    function (Vue, SvMixinCommon, SvMixinCommon, SvMixinGrid, SvMixinForm, SvMixinFormTab,
              SvCompHeader, SvCompHeaderBreadcrumbs, SvCompMessages, SvCompNav) {

    var SvApp = {
        el: '#sv-app',
        mixins: [SvMixinCommon],
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
    };

    new Vue(SvApp);

    return SvApp;
});