define(['sv-app', 'text!sv-comp-header-tpl', 'text!sv-comp-header-breadcrumbs-tpl',
        'text!sv-comp-header-search-tpl', 'text!sv-comp-header-favorites-tpl', 'text!sv-comp-header-account-tpl',
        'text!sv-comp-header-chat-tpl', 'text!sv-comp-header-local-notifications-tpl'],
    function(SvApp, headerTpl, headerBreadcrumbsTpl, headerSearchTpl, headerFavoritesTpl, headerAccountTpl,
             headerChatTpl, headerLocalNotificationsTpl) {
        //var dropdowns = {};

        var HeaderBreadcrumbs = {
            props: ['mobile'],
            mixins: [SvApp.mixins.common],
            template: headerBreadcrumbsTpl
        };

        var HeaderSearch = {
            props: ['mobile'],
            mixins: [SvApp.mixins.common],
            template: headerSearchTpl
        };

        var HeaderFavorites = {
            mixins: [SvApp.mixins.common],
            template: headerFavoritesTpl
        };

        var HeaderAccount = {
            mixins: [SvApp.mixins.common],
            template: headerAccountTpl
        };

        var HeaderLocalNotifications = {
            mixins: [SvApp.mixins.common],
            template: headerLocalNotificationsTpl,
            store: SvApp.store
        };

        var HeaderChat = {
            mixins: [SvApp.mixins.common],
            data: function () {
                return {
                    ui: {
                        curTab: 'users'
                    }
                }
            },
            template: headerChatTpl,
            store: SvApp.store
        };

        return {
            mixins: [SvApp.mixins.common],
            template: headerTpl,
            store: SvApp.store,
            components: {
                'sv-comp-header-breadcrumbs': HeaderBreadcrumbs,
                'sv-comp-header-search': HeaderSearch,
                'sv-comp-header-favorites': HeaderFavorites,
                'sv-comp-header-account': HeaderAccount,
                'sv-comp-header-local-notifications': HeaderLocalNotifications,
                'sv-comp-header-chat': HeaderChat
            },
            methods: {
                mainNavToggle: function () {
                    this.$store.commit('mainNavToggle');
                }
            }
        };
});