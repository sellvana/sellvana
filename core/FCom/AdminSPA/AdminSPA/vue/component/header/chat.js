define(['sv-mixin-common', 'text!sv-comp-header-chat-tpl'], function (SvMixinCommon, headerChatTpl) {

    var SvCompHeaderChat = {
        mixins: [SvMixinCommon],
        template: headerChatTpl,
        data: function () {
            return {
                ui: {
                    curTab: 'users'
                },
                cntMsgs: 0
            }
        },
    };

    return SvCompHeaderChat;
});