define(['sv-hlp'], function (SvHlp) {
    console.log('TEST');
    return {
        props: ['settings'],
        created: function () {
            console.log('CREATED');
        }
    }
});