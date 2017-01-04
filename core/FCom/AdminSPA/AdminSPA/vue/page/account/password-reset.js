define(['sv-hlp'], function(SvHlp) {
    return {
        mixins: [SvHlp.mixins.common],
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