<?php

class FCom_MarketClient_Main extends BClass
{
    public function downloadAndInstall($modules, $showProgress)
    {
        if ($showProgress) {
            echo '<h1>'.BLocale::_('Downloading and installing packages...').'</h1>';
        }
        $api = FCom_MarketClient_RemoteApi::i();
        $configUpdated = false;
        $i = 0;
        $cnt = sizeof($modules);
        foreach ($modules as $modName => $modInfo) {
            $i++;
            if ($showProgress) {
                echo '<br>'.BLocale::_('[%d/%d] Downloading: %s...', array($i, $cnt, $modName)).' ';
            }
            $filename = FCom_MarketClient_RemoteApi::i()->downloadPackage($modName, $modInfo['version']);
            if (!$filename) {
                $this->message('Could not download module package file: '.$modName.' ('.$modInfo['version'].')');
                continue;
            }
            $modNameArr = explode('_', $modName);
            $targetDir = BConfig::i()->get('fs/dlc_dir') . '/' . $modNameArr[0] .'/'. $modNameArr[1];
            BUtil::ensureDir($targetDir);

            if ($showProgress) {
                echo BLocale::_('Installing...').' ';
            }
            if (!BUtil::zipExtract($filename, $targetDir)) {
                $this->message('Could not extract module package file: '.$modName.' ('.$modInfo['version'].')');
                continue;
            }
            if (!empty($modInfo['enable'])) {
                $configUpdated = true;
                BConfig::i()->set('module_run_levels/FCom_Core/'.$modName, 'REQUESTED', false, true);
            }
            if ($showProgress) {
                echo 'DONE';
            }
        }
        if ($configUpdated) {
            FCom_Core_Main::i()->writeConfigFiles();
        }
    }
}
