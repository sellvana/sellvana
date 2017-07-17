define(['sv-mixin-common', 'text!sv-comp-header-account-tpl'], function (SvMixinCommon, headerAccountTpl) {

    var SvCompHeaderAccount = {
        mixins: [SvMixinCommon],
        template: headerAccountTpl,
        data: function () {
            return {
                user: this.$store.state.user,
                curStatus: {
                    value: 'online',
                    item_class: 'online',
                    icon_class: 'fa fa-check',
                    label: 'Online'
                },
                statuses: [
                    {value:'online', label:'Online', item_class:'online', icon_class:'fa fa-check'},
                    {value:'away', label:'Away', item_class:'away', icon_class:'fa fa-phone'},
                    {value:'na', label:'N/A', item_class:'na', icon_class:'fa fa-minus'},
                    {value:'offline', label:'Offline', item_class:'offline'}
                ]
            }
        },
        computed: {

        },
        methods: {
            setStatus: function (status) {
                this.curStatus = status;
            }
        }
    };

    return SvCompHeaderAccount;
});