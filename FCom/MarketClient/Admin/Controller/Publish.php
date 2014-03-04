<?php

class FCom_MarketClient_Admin_Controller_Publish extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client/publish';

    public function action_index()
    {
        $moduleNames = join(',', array_keys(BModuleRegistry::i()->getAllModules()));
        $result = FCom_MarketClient_RemoteApi::i()->getModulesVersions($moduleNames);
        $this->view('marketclient/publish')->set('modules', $result);
        $this->layout('/marketclient/publish');
    }

    public function action_module()
    {
        $modName = BRequest::i()->get('mod');
        $mod = BModuleRegistry::i()->module($modName);
        if (!$mod) {
            $this->forward(false);
            return;
        }
        $this->view('marketclient/publish/module')->set('mod', $mod);
        $this->layout('/marketclient/publish/module');
    }

    public function action_module__POST()
    {
        $modName = BRequest::i()->post('mod_name');
        /*
        $data = array(
            'mod_name' => $modName,
        );
        $result = FCom_MarketClient_RemoteApi::i()->publishModule($data);
        if (!empty($result['error'])) {
            BResponse::i()->redirect('marketclient/publish/module?mod='.$form['mod_name']);
            return;
        }
        */
        $hlp = FCom_MarketClient_RemoteApi::i();
        $result = $hlp->uploadPackage($modName);
        //TODO: handle $result
        $result = $hlp->requestSiteNonce();
        $url = $hlp->getUrl('market/module/edit', array('mod' => $modName));
        BResponse::i()->redirect($url);
    }

    public function action_upload()
    {

    }
}
