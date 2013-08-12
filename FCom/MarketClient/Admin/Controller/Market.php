<?php

class FCom_MarketClient_Admin_Controller_Market extends FCom_Admin_Controller_Abstract
{
    public function action_remote()
    {
        $this->view('market/remote')->url = FCom_MarketClient_RemoteApi::i()->getUrl('/market');
        $this->layout('/market/remote');
    }
}
