define(['vue', 'sv-mixin-form-tab'], function (Vue, SvMixinFormTab) {
    var Component = {
        mixins: [SvMixinFormTab],
        props: ['form', 'tab']
    };

    Vue.component('sv-page-default-form-tab', Component);

    return Component;
});