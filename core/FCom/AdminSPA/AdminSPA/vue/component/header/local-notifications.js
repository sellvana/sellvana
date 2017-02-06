define(['sv-hlp', 'text!sv-comp-header-local-notifications-tpl'], function (SvHlp, headerLocalNotificationsTpl) {

    var SvHeaderLocalNotifications = {
        mixins: [SvHlp.mixins.common],
        template: headerLocalNotificationsTpl,
        data: function () {
            return {
                notifications: [
                    {html: '<img src="" alt="img" class="img-circle"/><a href="#" @click.prevent>New User</a><span>registered</span><span class="time">1 min ago</span>'},
                    {html: '<img src="" alt="img" class="img-circle"/><a href="#">Product 931</a><span>add to wishlist</span><span class="time">1 min ago</span>'},
                    {html: '<img src="" alt="img" class="img-circle"/><a href="#">Product 931</a><span>add to wishlist</span><span class="time">1 min ago</span>'}
                ]
            };
        }
    };

    return SvHeaderLocalNotifications;
});