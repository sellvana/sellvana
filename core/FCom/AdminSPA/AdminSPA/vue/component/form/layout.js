define(['vue', 'sv-hlp', 'text!sv-comp-form-layout-tpl'], function (Vue, SvHlp, layoutTpl) {
    var Component = {
        mixins: [SvHlp.mixins.common],
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