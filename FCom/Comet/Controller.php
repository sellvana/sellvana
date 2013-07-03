<?php

class FCom_Comet_Controller extends FCom_Core_Controller_Abstract
{
    public function getUrl()
    {
        return BApp::m('FCom_Comet')->baseSrc();
    }

    public function action_index()
    {
        set_time_limit(0);
        $delay = BConfig::i()->get('modules/FCom_Comet/delay_sec');
        $client = FCom_Comet_Model_Client::i()->sessionClient();
        $client->checkIn(BRequest::i()->json());
        while (true) {
            $messages = $client->getPushQueue();
            if ($messages) {
                break;
            } else {
                sleep(1);
            }
        }
        $client->checkOut();
        BResponse::i()->json($messages);
    }
}