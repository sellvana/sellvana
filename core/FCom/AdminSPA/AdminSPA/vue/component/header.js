define(['sv-hlp', 'text!sv-comp-header-tpl', 'text!sv-comp-header-breadcrumbs-tpl',
        'text!sv-comp-header-search-tpl', 'text!sv-comp-header-favorites-tpl', 'text!sv-comp-header-account-tpl',
        'text!sv-comp-header-chat-tpl', 'text!sv-comp-header-local-notifications-tpl'],
    function(SvHlp, headerTpl, headerBreadcrumbsTpl, headerSearchTpl, headerFavoritesTpl, headerAccountTpl,
             headerChatTpl, headerLocalNotificationsTpl) {
        //var dropdowns = {};

        var HeaderBreadcrumbs = {
            props: ['mobile'],
            mixins: [SvHlp.mixins.common],
            store: SvHlp.store,
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
                        SvHlp.sendRequest('POST', 'favorites/remove', cur, function (response) {

                        });
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
                        SvHlp.sendRequest('POST', 'favorites/add', cur, function (response) {

                        });
                    }
                }
            }
        };

        var HeaderSearch = {
            props: ['mobile'],
            mixins: [SvHlp.mixins.common],
            template: headerSearchTpl,
            data: function () {
                return {
                    query: '',
                    results: []
                }
            },
            methods: {
                submitSearch: function () {
                    SvHlp.sendRequest('GET', '/header/search', {q: this.query}, function (response) {
                        if (response.link) {
                            SvHlp.router.push(response.link);
                        }
                    });
                }
            }
        };

        var HeaderFavorites = {
            mixins: [SvHlp.mixins.common],
            template: headerFavoritesTpl,
            store: SvHlp.store,
            computed: {
                favorites: function () {
                    return this.$store.state.favorites || [];
                },
                isActive: function () {
                    var vm = this;
                    return function (fav) {
                        return fav.link === this.$route.fullPath;
                    }
                }
            },
            methods: {
                removeFavorite: function (fav) {
                    this.$store.commit('removeFavorite', fav);
                    SvHlp.sendRequest('POST', 'favorites/remove', fav, function (response) {

                    });
                }
            }
        };

        var HeaderAccount = {
            mixins: [SvHlp.mixins.common],
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
            mixins: [SvHlp.mixins.common],
            template: headerLocalNotificationsTpl,
            store: SvHlp.store
        };

        var HeaderChat = {
            mixins: [SvHlp.mixins.common],
            data: function () {
                return {
                    ui: {
                        curTab: 'users'
                    }
                }
            },
            template: headerChatTpl,
            store: SvHlp.store
        };

        return {
            mixins: [SvHlp.mixins.common],
            template: headerTpl,
            store: SvHlp.store,
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
                },
                changeBodyClass: function (ev) {
                    $('body').removeClass().addClass(ev.target.value);
                }
            }
        };
});
