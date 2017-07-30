define(['vue', 'sv-mixin-common', 'text!sv-comp-actions-tpl'], function (Vue, SvMixinCommon, actionsTpl) {
    var SvCompFormActions = {
        mixins: [SvMixinCommon],
        props: {
            'groups': {type: Object},
            'container-class': {type: String},
            'event-name': {type: String, default:'action'}
        },
        template: actionsTpl,
        computed: {
            desktop_groups: function () {
                //console.log(this.groups);
                return this.groups ? this.groups.desktop : [];
            },
            mobile_groups: function () {
                //console.log(this.groups);
                return this.groups ? this.groups.mobile : [];
            }
        },
        methods: {
            doAction: function (action) {
                console.log(this.eventName, action);
                this.$emit('event', this.eventName, action);
            }
        }
    };

    Vue.component('sv-comp-actions', SvCompFormActions);

    return SvCompFormActions;
});