<?php

/**
 * Class Sellvana_MarketClient_Admin_Controller_Module
 *
 * @property Sellvana_MarketClient_RemoteApi $Sellvana_MarketClient_RemoteApi
 * @property Sellvana_MarketClient_Main $Sellvana_MarketClient_Main
 */
class Sellvana_MarketClient_Admin_Controller_Module extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/install';

    public function action_install()
    {
        //$result = $this->Sellvana_MarketClient_RemoteApi->requestSiteNonce();
        $modName = $this->BRequest->get('mod_name');
        $result = $this->Sellvana_MarketClient_RemoteApi->getModuleInstallInfo($modName);
        $this->layout('/marketclient/module/install');
        $this->view('marketclient/install')->set('install', $result);
    }

    public function action_install__POST()
    {
        $this->BResponse->startLongResponse(false);

        $r = $this->BRequest;
        $modules = $r->post('modules');
        $redirectUrl = $r->request('redirect_to');

        if (!$r->isUrlLocal($redirectUrl)) {
            $redirectUrl = '';
        }

        $this->Sellvana_MarketClient_Main->progress([], true);
        $this->Sellvana_MarketClient_Main->downloadAndInstall($modules);

        $this->BResponse->redirect($redirectUrl ? $redirectUrl : 'modules');
    }
}
