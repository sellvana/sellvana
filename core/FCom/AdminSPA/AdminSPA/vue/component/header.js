define(['sv-app', 'text!sv-comp-header-tpl'], function(SvApp, headerTpl) {
    return {
        props: ['navs'],
        data: function() {
            return {
                ui: {

                }
            }
        },
        methods: {
            svAsset: SvApp.methods.assetUrl
        },
        template: headerTpl
    }
});