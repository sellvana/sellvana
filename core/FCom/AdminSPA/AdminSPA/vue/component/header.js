define(['sv-app', 'text!sv-comp-header-tpl', 'text!sv-comp-header-breadcrumbs-tpl',
        'text!sv-comp-header-search-tpl', 'text!sv-comp-header-favorites-tpl', 'text!sv-comp-header-account-tpl',
        'text!sv-comp-header-chat-tpl', 'text!sv-comp-header-local-notifications-tpl'],
    function(SvApp, headerTpl, headerBreadcrumbsTpl, headerSearchTpl, headerFavoritesTpl, headerAccountTpl,
             headerChatTpl, headerLocalNotificationsTpl) {
        //var dropdowns = {};

        var HeaderBreadcrumbs = {
            props: ['mobile'],
            mixins: [SvApp.mixins.common],
            store: SvApp.store,
            template: headerBreadcrumbsTpl,
            computed: {
                breadcrumbParts: function () {
                    return this.$store.state.curPage.breadcrumbs;
                },
                curPage: function () {
                    return this.$store.state.curPage;
                },
                isFavorite: function () {
                    var favs = this.$store.state.favorites || [], curLink = this.$store.state.curPage.link;
                    for (var i = 0; i < favs.length; i++) {
                        if (favs[i].link === curLink) {
                            return true;
                        }
                    }
                    return false;
                }
            },
            methods: {
                toggleFavorite: function () {
                    var curPage = this.$store.state.curPage;
                    if (this.isFavorite) {
                        var cur = {link: curPage.link};
                        this.$store.commit('removeFavorite', cur);
                    } else {
                        var labelArr = [], iconClass = null;
                        for (var i = 0; i < curPage.breadcrumbs.length; i++) {
                            var part = curPage.breadcrumbs[i];
                            labelArr.push(part.label);
                            if (part.icon_class) {
                                iconClass = part.icon_class;
                            }
                        }
                        labelArr.push(curPage.label);
                        var cur = {link: curPage.link, label: labelArr.join(' > '), icon_class: iconClass};
                        this.$store.commit('addFavorite', cur);
                    }
                }
            }
        };

        var HeaderSearch = {
            props: ['mobile'],
            mixins: [SvApp.mixins.common],
            template: headerSearchTpl
        };

        var HeaderFavorites = {
            mixins: [SvApp.mixins.common],
            template: headerFavoritesTpl,
            store: SvApp.store,
            computed: {
                favorites: function () {
                    return this.$store.state.favorites || [];
                }
            },
            methods: {
                removeFavorite: function (fav) {
                    this.$store.commit('removeFavorite', fav);
                }
            }
        };

        var HeaderAccount = {
            mixins: [SvApp.mixins.common],
            template: headerAccountTpl,
            data: function () {
                return {
                    curStatus: {
                        value: 'online',
                        item_class: 'online',
                        icon_class: 'fa fa-check',
                        label: 'Online'
                    },
                    statuses: [
                        {value:'online', label:'Online', item_class:'online', icon_class:'fa fa-check'},
                        {value:'away', label:'Away', item_class:'away', icon_class:'fa fa-phone'},
                        {value:'na', label:'N/A', item_class:'na', icon_class:'fa fa-minus'},
                        {value:'offline', label:'Offline', item_class:'offline'}
                    ]
                }
            },
            computed: {

            },
            methods: {
                setStatus: function (status) {
                    this.curStatus = status;
                }
            }
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