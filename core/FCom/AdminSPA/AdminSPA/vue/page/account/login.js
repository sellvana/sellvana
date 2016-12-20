define(['jquery', 'sv-app'], function($, SvApp) {
    return {
        mixins: [SvApp.mixins.common],
        store: SvApp.store,
        data: function () {
            return {
                username: '',
                password: ''
            }
        },
        methods: {
            submit: function() {
                var postData = {login: {username: this.username, password: this.password}};
                SvApp.methods.sendRequest('POST', 'account/login', postData, function (response) {
                    SvApp.router.push(response._redirect);
                });
            }
        }
    }
});