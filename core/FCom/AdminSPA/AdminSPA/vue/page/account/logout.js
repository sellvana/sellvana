define([], function() {
    return {
        computed: {
            isLoggedIn: function () {
                return this.$store.state.user && this.$store.state.user.id;
            }
        },
        created: function () {
            if (!this.$store.state.user) {
                this.$router.push('/login');
            }
            var postData = {}, vm = this;
            this.sendRequest('POST', 'auth/logout', postData, function (response) {
                vm.processResponse(response);
                vm.$router.push("/login");
            });
        },
        mounted: function () {
            $('body').addClass('sv-login');
        },
        beforeDestroy: function () {
            $('body').removeClass('sv-login');
        }
    }
});