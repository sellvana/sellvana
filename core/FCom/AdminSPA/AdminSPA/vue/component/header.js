define(['sv-app', 'text!sv-comp-header-tpl'], function(SvApp, headerTpl) {
    return {
        props: ['navs'],
        data: function() {
            return {
                ui: {
                    dropdowns: {
                        _current: false,
                        chats: false
                    }
                }
            }
        },
        methods: {
            svAsset: SvApp.methods.assetUrl
        },
        template: headerTpl
    }
});