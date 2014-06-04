<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_MarketClient_Admin_Controller_Module extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/install';

    public function action_install()
    {
        //$result = $this->FCom_MarketClient_RemoteApi->requestSiteNonce();
        $modName = $this->BRequest->get('mod_name');
        $result = $this->FCom_MarketClient_RemoteApi->getModuleInstallInfo($modName);
        $this->view('marketclient/install')->set('install', $result);
        $this->layout('/marketclient/module/install');
    }

    public function action_install__POST()
    {
        $this->BResponse->startLongResponse(false);

        $modules = $this->BRequest->post('modules');
        $redirectUrl = $this->BRequest->request('redirect_to');

        if (!$r->isUrlLocal($redirectUrl)) {
            $redirectUrl = '';
        }

        $this->FCom_MarketClient_Main->progress([], true);
        $this->FCom_MarketClient_Main->downloadAndInstall($modules);

        $this->BResponse->redirect($redirectUrl ? $redirectUrl : 'modules');
    }
}
