define([], function() {
    return {
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