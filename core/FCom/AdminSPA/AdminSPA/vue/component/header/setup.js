define(['jquery', 'sv-hlp', 'text!sv-comp-header-setup-tpl'], function ($, SvHlp, svCompHeaderSetupTpl) {
    var SvCompHeaderSetup = {
        mixins: [SvHlp.mixins.common],
        template: svCompHeaderSetupTpl,
        data: function () {
            return {
                setup: {
                    progress: 50,
                    steps: [
                        {id:'basic', label:'Basic Information', link:'/profile', done:1},
                        {id:'company', label:'Company Profile', link:'/users/form?id=1', done:1},
                        {id:'user', label:'Global Configuration', link:'/settings', done:0},
                        {id:'vendor_group', label:'Default Vendor Group Profile', link:'/customers/form?id=1', done:0}
                    ]
                }
            }
        }
    };

    return SvCompHeaderSetup;
});