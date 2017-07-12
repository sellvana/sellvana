define(['vue', 'sv-hlp', 'text!sv-comp-actions-tpl'], function (Vue, SvHlp, actionsTpl) {
    var SvCompFormActions = {
        mixins: [SvHlp.mixins.common],
        props: {
            'groups': {type: Object},
            'container-class': {type: String, default: 'f-actions-container'}
        },
        template: actionsTpl,
        computed: {
            desktop_groups: function () {
                return this.groups ? this.groups.desktop : [];
            },
            mobile_groups: function () {
                return this.groups ? this.groups.mobile : [];
            }
        },
        methods: {
            doFormAction: function (action) {
                this.$emit('event', 'do_action', action);
            }
        }
    };

    Vue.component('sv-comp-actions', SvCompFormActions);

    return SvCompFormActions;
});