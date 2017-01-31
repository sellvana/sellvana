define(['vue', 'text!sv-comp-form-tpl'], function(Vue, formTpl) {
    var SvCompForm = {
        props: ['form'],
        template: formTpl
    };

    Vue.component('sv-comp-form', SvCompForm);

    return SvCompForm;
});