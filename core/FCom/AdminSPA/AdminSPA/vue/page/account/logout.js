define(['sv-hlp'], function(SvHlp) {
    return {
        mixins: [SvHlp.mixins.common],
        store: SvHlp.store,
        computed: {
            isLoggedIn: function () {
                return this.$store.state.user && this.$store.state.user.id;
            }
        },
        mounted: function () {
            if (!this.$store.state.user) {
                this.$router.push('/login');
            }
            var postData = {}, vm = this;
            SvHlp.sendRequest('POST', 'account/logout', postData, function (response) {
                SvHlp.processResponse(response);
                vm.$router.push("/login");
            });
        }
    }
});