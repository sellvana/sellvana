define(['text!sv-comp-header-favorites-tpl'], function (headerFavoritesTpl) {

    var SvCompHeaderFavorites = {
        template: headerFavoritesTpl,
        computed: {
            favorites: function () {
                return this.$store.state.favorites || [];
            }
        },
        methods: {
            removeFavorite: function (fav) {
                this.$store.commit('removeFavorite', fav);
                this.sendRequest('POST', 'favorites/remove', fav, function (response) {

                });
            },
            isActive: function (fav) {
                return fav.link === this.$route.fullPath;
            }
        }
    };
    return SvCompHeaderFavorites;
});