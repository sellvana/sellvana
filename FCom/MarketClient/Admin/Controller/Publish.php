<?php

class FCom_MarketClient_Admin_Controller_Publish extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
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
        }
        */
        FCom_MarketClient_RemoteApi::i()->uploadPackage($modName);
    }

    public function action_upload()
    {

    }
}
