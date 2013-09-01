
define(['jquery', 'underscore', 'backbone', 'fcom.pushclient', 'exports'], function($, _, Backbone, PushClient, exports)
{
    PushClient.listen(/^adminchat$/, channel_adminchat);

    function status(options) {
        PushClient.send({channel:'adminchat', signal:'status', status:options.status});
    }

    function start(options) {
        PushClient.send({channel:'adminchat', signal:'start', user:options.user});
    }

    function channel_adminchat(msg)
    {

    }

    _.extend(exports, {
        status: status,
        start: start
    });
});

