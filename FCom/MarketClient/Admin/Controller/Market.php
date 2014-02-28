<?php

class FCom_MarketClient_Admin_Controller_Market extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/remote';

    public function action_remote()
    {
        $this->layout('/marketclient/remote');
    }
}
