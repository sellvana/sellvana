<?php

class FCom_PushServer_Service_Default extends FCom_PushServer_Service_Abstract
{
    public function init()
    {
        $client = FCom_PushServer_Model_Client::i()->sessionClient();
        $channelName = 'session:' . $client->get('session_id');
        $channel = FCom_PushServer_Model_Channel::i()->getChannel($channelName, true);
        $channel->subscribeService(array($this, 'channel_session'));
    }

    public function channel_session($args)
    {
        $message = $args['message'];

    }

    public function message_subscribe($msg)
    {

    }
}
