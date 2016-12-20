define(['sv-app'], function(SvApp) {
    return {
        mixins: [SvApp.mixins.common],
        data: function () {
            return {
                new_password: '',
                confirm_password: ''
            }
        },
        methods: {
            submit: function() {
                console.log('RESET SUBMIT');
            }
        }
    }
});