<?php

class FCom_MarketClient_Admin_Controller_Market extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/remote';

    public function action_connect()
    {
        $data = FCom_MarketClient_RemoteApi::i()->setupConnection();
        if (empty($data['url'])) {
            $data['url'] = 'modules';
            $this->message('Could not connect to Marketplace', 'error');
        }
        BResponse::i()->redirect($data['url']);
    }

}
