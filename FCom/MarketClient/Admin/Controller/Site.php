<?php

class FCom_MarketClient_Admin_Controller_Site extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client';

    public function action_connect()
    {
        $data = FCom_MarketClient_RemoteApi::i()->setupConnection();
        if (empty($data['url'])) {
            $data['url'] = 'modules';
            $this->message('Could not connect to Marketplace', 'error');
        }
        BResponse::i()->redirect($data['url']);
    }

    public function action_check_updates__POST()
    {
        try {
            FCom_MarketClient_RemoteApi::i()->getModulesVersions(true, true);
            $this->message('Updates retrieved successfully');
        } catch (Exception $e) {
            $this->message($e->getMessage());
        }
        BResponse::i()->redirect(BRequest::i()->referrer());
    }
}
