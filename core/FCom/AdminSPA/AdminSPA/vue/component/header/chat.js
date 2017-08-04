define(['text!sv-comp-header-chat-tpl'], function (headerChatTpl) {

    var SvCompHeaderChat = {
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