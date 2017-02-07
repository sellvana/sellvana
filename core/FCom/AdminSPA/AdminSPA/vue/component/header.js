define(['vue', 'sv-hlp', 'text!sv-comp-header-tpl',
        'sv-comp-header-breadcrumbs', 'sv-comp-header-search', 'sv-comp-header-favorites', 'sv-comp-header-account',
        'sv-comp-header-local-notifications', 'sv-comp-header-chat',

        'text!sv-comp-header-breadcrumbs-tpl', 'text!sv-comp-header-search-tpl', 'text!sv-comp-header-favorites-tpl',
        'text!sv-comp-header-account-tpl', 'text!sv-comp-header-chat-tpl', 'text!sv-comp-header-local-notifications-tpl'
    ],
    function(Vue, SvHlp, headerTpl,
             SvCompHeaderBreadcrumbs, SvCompHeaderSearch, SvCompHeaderFavorites, SvCompHeaderAccount,
             SvCompHeaderLocalNotifications, SvCompHeaderChat,

             headerBreadcrumbsTpl, headerSearchTpl, headerFavoritesTpl,
             headerAccountTpl, headerChatTpl, headerLocalNotificationsTpl
    ) {

        var SvCompHeader = {
            mixins: [SvHlp.mixins.common],
            template: headerTpl,
            components: {
                'sv-comp-header-breadcrumbs': SvCompHeaderBreadcrumbs,
                'sv-comp-header-search': SvCompHeaderSearch,
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