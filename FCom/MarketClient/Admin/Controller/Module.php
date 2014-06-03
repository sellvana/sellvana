<?php defined('BUCKYBALL_ROOT_DIR') || die();

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

        $modules = BRequest::i()->post('modules');
        $redirectUrl = BRequest::i()->request('redirect_to');

        if (!$r->isUrlLocal($redirectUrl)) {
            $redirectUrl = '';
        }

        FCom_MarketClient_Main::i()->progress([], true);
        FCom_MarketClient_Main::i()->downloadAndInstall($modules);

        BResponse::i()->redirect($redirectUrl ? $redirectUrl : 'modules');
    }
}
