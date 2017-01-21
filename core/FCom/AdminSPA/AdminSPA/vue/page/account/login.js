define(['jquery', 'sv-hlp'], function($, SvHlp) {
    return {
        mixins: [SvHlp.mixins.common],
        data: function () {
            return {
                username: '',
                password: '',
                remember_me: false
            }
        },
        methods: {
            submit: function() {
                var postData = {login: {username: this.username, password: this.password, remember_me: this.remember_me}};
                SvHlp.sendRequest('POST', 'account/login', postData, function (response) {
                    if (response._redirect) {
                        SvHlp.router.push(response._redirect);
                    }
                });
            }
        },
        created: function () {
            if (this.$store.state.user && this.$store.state.user.id) {
                var vm = this;
                SvHlp.sendRequest('GET', 'account/login', {}, function (response) {
                    console.log(response);
                    if (response.is_logged_in) {
                        vm.$router.push('/');
                    } else {
                        vm.$store.commit('setData', {user: false});
                    }
                });
            }
        }
    }
});