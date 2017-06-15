define(['vue', 'sv-hlp', 'text!sv-comp-form-actions-tpl'], function (Vue, SvHlp, actionsTpl) {
    var SvCompFormActions = {
        mixins: [SvHlp.mixins.common],
        props: ['form', 'container-class'],
        template: actionsTpl,
        methods: {
            doFormAction: function (action) {
                this.$emit('event', 'form_action', action);
            }
        }
    };

    Vue.component('sv-comp-form-actions', SvCompFormActions);

    return SvCompFormActions;
});