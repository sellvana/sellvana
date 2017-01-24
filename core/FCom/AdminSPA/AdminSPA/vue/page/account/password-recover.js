define(['sv-hlp'], function(SvHlp) {
    return {
        mixins: [SvHlp.mixins.common],
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