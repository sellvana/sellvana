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
        $modName = BRequest::i()->get('mod_name');
        $mod = BModuleRegistry::i()->module($modName);
        if (!$mod) {
            $this->forward(false);
            return;
        }
        $this->view('marketclient/publish/module')->set('mod_name', $mod);
        $this->layout('/marketclient/publish/module');
    }

    public function action_module__POST()
    {
        $hlp = FCom_MarketClient_RemoteApi::i();
        $connResult = $hlp->setupConnection();

        $modName = BRequest::i()->post('mod_name');
        $versionResult = $hlp->getModulesVersions($modName);
        #var_dump($modName, $versionResult); exit;
        if (!empty($versionResult[$modName]) && $versionResult[$modName]['status']==='available') {
            $createResult = $hlp->createModule($modName);
            if (!empty($createResult['error'])) {
                $this->message($createResult['error'], 'error');
                BResponse::i()->redirect('marketclient/publish');
                return;
            }
        }
        $uploadResult = $hlp->uploadPackage($modName);
        //TODO: handle $result
        $url = $hlp->getUrl('market/module/edit', array('mod_name' => $modName));
        BResponse::i()->redirect($url);
    }

    public function action_upgrade__POST()
    {

    }
}
