define(['sv-app'], function(SvApp) {
    return {
        mixins: [SvApp.mixins.common],
        data: function () {
            return {
                username: 'USERNAME',
            }
        },
        methods: {
            submit: function() {
                console.log('RECOVER SUBMIT');
            }
        }
    }
});