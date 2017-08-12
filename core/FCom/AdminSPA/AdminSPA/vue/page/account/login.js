define(['jquery'], function($) {
    return {
        data: function () {
            return {
                username: '',
                password: '',
                remember_me: false,
                logging_in: false
            }
        },
        methods: {
            submit: function() {
                var vm = this, postData = {
                    login: {
                        username: this.username,
                        password: this.password,
                        remember_me: this.remember_me
                    }
                };
                this.logging_in = true;
                this.sendRequest('POST', 'auth/login', postData, function (response) {
					
                    vm.logging_in = false;
                    if (response._redirect) {
                        vm.$router.push(response._redirect);
                    }
                });
            }
        },
        created: function () {
            if (this.$store.state.user && this.$store.state.user.id) {
                var vm = this;
                this.sendRequest('GET', 'auth/login', {}, function (response) {
                    console.log(response);
                    if (response.is_logged_in) {
                        vm.$router.push('/');
                    } else {
                        vm.$store.commit('setData', {user: false});
                    }
                });
            }
        },
        mounted: function () {
            $('body').addClass('sv-login');
        },
        beforeDestroy: function () {
            $('body').removeClass('sv-login');
        }
    }
});