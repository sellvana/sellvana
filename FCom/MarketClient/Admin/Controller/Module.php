<?php

class FCom_MarketClient_Admin_Controller_Module extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/install';

    public function action_install()
    {
        //$result = FCom_MarketClient_RemoteApi::i()->requestSiteNonce();
        $modName = BRequest::i()->get('mod_name');
        $result = FCom_MarketClient_RemoteApi::i()->getModuleInstallInfo($modName);
        $this->view('marketclient/install')->set('install', $result);
        $this->layout('/marketclient/module/install');
    }

    public function action_install__POST()
    {
        BResponse::i()->startLongResponse(false);

        $modules = BRequest::i()->post('install');
        $showProgress = BRequest::i()->request('show_progress');

        FCom_MarketClient_Main::i()->downloadAndInstall($modules, $showProgress);

        $redirectUrl = BReuest::i()->request('redirect_to');
        BResponse::i()->redirect($redirectUrl ? $redirectUrl : 'modules');
    }

    public function action_upgrade()
    {
        $this->layout('/marketclient/module/upgrade');
    }
}
