<?php

class FCom_MarketClient_Admin_Controller_Module extends FCom_Admin_Controller_Abstract
{
    public function action_install()
    {
        //$result = FCom_MarketClient_RemoteApi::i()->requestSiteNonce();
        $modName = BRequest::i()->get('mod_name');
        $result = FCom_MarketClient_RemoteApi::i()->getModuleInstallInfo($modName);
        $this->view('marketclient/install')->set('install', $result);
        $this->layout('/marketclient/install');
    }

    public function action_install__POST()
    {
        $install = BRequest::i()->post('install');
        BResponse::i()->startLongResponse();
        $api = FCom_MarketClient_RemoteApi::i();

        $configUpdated = false;
        foreach ($install as $modName => $modInfo) {
            $filename = FCom_MarketClient_RemoteApi::i()->downloadPackage($modName, $modInfo['version']);
            if (!$filename) {
                $this->message('Could not download module package file: '.$modName.' ('.$modInfo['version'].')');
                continue;
            }
            $modNameArr = explode('_', $modName);
            $targetDir = BConfig::i()->get('fs/dlc_dir') . '/' . $modNameArr[0] .'/'. $modNameArr[1];
            BUtil::ensureDir($targetDir);
            if (!BUtil::zipExtract($filename, $targetDir)) {
                $this->message('Could not extract module package file: '.$modName.' ('.$modInfo['version'].')');
                continue;
            }
            if (!empty($modInfo['enable'])) {
                $configUpdated = true;
                BConfig::i()->set('module_run_levels/FCom_Core/'.$modName, 'REQUESTED', false, true);
            }
        }
        if ($configUpdated) {
            FCom_Core_Main::i()->writeConfigFiles();
        }
    }
}
