define(['sv-app'], function (SvApp) {
    console.log('TEST');
    return {
        props: ['settings'],
        create: function () {
            console.log('CREATED');
        }
    }
});