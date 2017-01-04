define(['jquery', 'sv-hlp'], function($, SvHlp) {
    return {
        mixins: [SvHlp.mixins.common],
        store: SvHlp.store,
        data: function () {
            return {
                username: '',
                password: ''
            }
        },
        methods: {
            submit: function() {
                var postData = {login: {username: this.username, password: this.password}};
                SvHlp.sendRequest('POST', 'account/login', postData, function (response) {
                    if (response._redirect) {
                        SvHlp.router.push(response._redirect);
                    }
                });
            }
        },
        created: function () {
            if (this.$store.state.user && this.$store.state.user.id) {
                this.$router.push('/');
            }
        }
    }
});