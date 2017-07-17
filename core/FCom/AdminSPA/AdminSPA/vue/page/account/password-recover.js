define(['sv-mixin-common'], function(SvMixinCommon) {
    return {
        mixins: [SvMixinCommon],
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