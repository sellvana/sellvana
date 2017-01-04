define(['sv-hlp'], function (SvHlp) {
    console.log('TEST');
    return {
        props: ['settings'],
        create: function () {
            console.log('CREATED');
        }
    }
});