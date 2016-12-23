define(['jquery', 'sv-app'], function($, SvApp) {
    return {
        mixins: [SvApp.mixins.common],
        store: SvApp.store,
        computed: {
            isLoggedIn: function () {
                return this.$store.state.user && this.$store.state.user.id;
            }
        },
        mounted: function () {
            var postData = {};
            SvApp.methods.sendRequest('POST', 'account/logout', postData, function (response) {

            });
        }
    }
});