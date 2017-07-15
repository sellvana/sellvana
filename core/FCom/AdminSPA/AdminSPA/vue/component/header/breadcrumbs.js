define(['sv-mixin-common', 'text!sv-comp-header-breadcrumbs-tpl'], function (SvMixinCommon, headerBreadcrumbsTpl) {
    var SvCompHeaderBreadcrumbs = {
        props: ['mobile'],
        mixins: [SvMixinCommon],
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
                    this.sendRequest('POST', 'favorites/remove', cur, function (response) {

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
                    this.sendRequest('POST', 'favorites/add', cur, function (response) {

                    });
                }
            }
        }
    };

    return SvCompHeaderBreadcrumbs;

});