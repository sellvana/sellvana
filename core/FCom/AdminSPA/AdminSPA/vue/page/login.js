define([], function() {
    return {
        data: function () {
            return {
                username: 'USERNAME',
                password: 'PASSWORD'
            }
        },
        methods: {
            submit: function() {
                console.log('LOGIN SUBMIT');
            }
        }
    }
});