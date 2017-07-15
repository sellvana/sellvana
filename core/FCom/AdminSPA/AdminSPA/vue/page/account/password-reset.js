define(['sv-mixin-common'], function(SvMixinCommon) {
    return {
        mixins: [SvMixinCommon],
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