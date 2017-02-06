define(['sv-hlp', 'text!sv-comp-header-favorites-tpl'], function (SvHlp, headerFavoritesTpl) {

    var SvCompHeaderFavorites = {
        mixins: [SvHlp.mixins.common],
        template: headerFavoritesTpl,
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
                this.sendRequest('POST', 'favorites/remove', fav, function (response) {

                });
            }
        }
    };

    return SvCompHeaderFavorites;
});