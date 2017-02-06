define(['sv-hlp', 'text!sv-comp-header-chat-tpl'], function (SvHlp, headerChatTpl) {

    var SvCompHeaderChat = {
        mixins: [SvHlp.mixins.common],
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