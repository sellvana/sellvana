define(['jquery', 'backbone', 'fcom.pushclient', 'exports'], function($, _, PushClient, exports)
{
    PushClient.send({ channel:'adminchat', message:'subscribe' });
    PushClient.listen({ channel: 'adminchat', callback:channel_adminchat });

    function channel_adminchat(msg)
    {

    }

    /*_.extend(exports, {

    });*/
});
