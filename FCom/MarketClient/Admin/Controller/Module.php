<?php

class FCom_MarketClient_Admin_Controller_Module extends FCom_Admin_Controller_Abstract
{
    public function action_install()
    {
        //$result = FCom_MarketClient_RemoteApi::i()->requestSiteNonce();
        $modName = BRequest::i()->get('mod_name');
        FCom_MarketClient_RemoteApi::i()->getModuleInstallInfo($modName);
        $this->layout('/marketclient/install');
    }

    public function action_install__POST()
    {

    }
}
