define(['vue', 'sv-mixin-common', 'text!sv-comp-form-layout-tpl'], function (Vue, SvMixinCommon, layoutTpl) {
    var Component = {
        mixins: [SvMixinCommon],
        template: layoutTpl,
        props: ['form', 'layout'],
        data: function () {
            return {
            }
        }
    };

    Vue.component('sv-comp-form-layout', Component);

    return Component;
});