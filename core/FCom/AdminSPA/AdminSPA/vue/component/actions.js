define(['vue', 'text!sv-comp-actions-tpl'], function (Vue, actionsTpl) {
    var SvCompActions = {
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
                this.emitEvent(this.eventName, action);
            }
        }
    };

    Vue.component('sv-comp-actions', SvCompActions);

    return SvCompActions;
});