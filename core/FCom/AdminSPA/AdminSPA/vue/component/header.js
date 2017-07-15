define(['vue', 'sv-mixin-common', 'text!sv-comp-header-tpl',
        'sv-comp-header-breadcrumbs', 'sv-comp-header-search', 'sv-comp-header-favorites', 'sv-comp-header-account',
        'sv-comp-header-local-notifications', 'sv-comp-header-chat', 'sv-comp-header-setup',

        'text!sv-comp-header-breadcrumbs-tpl', 'text!sv-comp-header-search-tpl', 'text!sv-comp-header-favorites-tpl',
        'text!sv-comp-header-account-tpl', 'text!sv-comp-header-chat-tpl', 'text!sv-comp-header-local-notifications-tpl',
        'text!sv-comp-header-setup-tpl'
    ],
    function(Vue, SvMixinCommon, headerTpl,
             SvCompHeaderBreadcrumbs, SvCompHeaderSearch, SvCompHeaderFavorites, SvCompHeaderAccount,
             SvCompHeaderLocalNotifications, SvCompHeaderChat, SvCompHeaderSetup,

             headerBreadcrumbsTpl, headerSearchTpl, headerFavoritesTpl,
             headerAccountTpl, headerChatTpl, headerLocalNotificationsTpl, headerSetupTpl
    ) {

        var SvCompHeader = {
            mixins: [SvMixinCommon],
            template: headerTpl,
            components: {
                'sv-comp-header-breadcrumbs': SvCompHeaderBreadcrumbs,
                'sv-comp-header-search': SvCompHeaderSearch,
                'sv-comp-header-setup': SvCompHeaderSetup,
                'sv-comp-header-favorites': SvCompHeaderFavorites,
                'sv-comp-header-account': SvCompHeaderAccount,
                'sv-comp-header-local-notifications': SvCompHeaderLocalNotifications,
                'sv-comp-header-chat': SvCompHeaderChat
            },
            methods: {
                mainNavToggle: function () {
                    this.$store.commit('mainNavToggle');
                },
                changeBodyClass: function (ev) {
                    $('body').removeClass().addClass(ev.target.value);
                }
            }
        };

        Vue.component('sv-comp-header', SvCompHeader);

        return SvCompHeader;
});
