<?php

/**
 * Class Sellvana_CustomerAssist_PushServer_Customer
 *
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 */
class Sellvana_CustomerAssist_PushServer_Customer extends FCom_PushServer_Service_Abstract
{
    public function signal_request()
    {
        $channelName = $this->_message['channel'];
        $channel = $this->FCom_PushServer_Model_Channel->getChannel($channelName, true);
        $channel->subscribe($this->_client);

        $sessionId = $this->BRequest->sanitizeOne($this->_message['session_id'], 'alnum');

        $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
            'href' => $this->BApp->adminHref('customer_assist/help_me?session_id=' . $sessionId),
            'content' => $this->_('Customer has requested assistance: %s', [$sessionId]),
        ]);
    }
}
