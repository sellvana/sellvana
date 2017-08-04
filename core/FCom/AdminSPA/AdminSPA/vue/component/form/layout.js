define(['vue', 'text!sv-comp-form-layout-tpl'], function (Vue, layoutTpl) {
    var Component = {
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