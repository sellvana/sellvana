define(['vue', 'sv-hlp'], function (Vue, SvHlp) {
    var Component = {
        mixins: [SvHlp.mixins.formTab],
        props: ['form', 'tab']
    };

    Vue.component('sv-page-default-form-tab', Component);

    return Component;
});