<?php

class FCom_MarketClient_Main extends BClass
{
    public function progress($data = null, $reset = false)
    {
        $progress = !$reset ? BCache::i()->load('marketclient_progress') : array();
        if (!empty($data)) {
            $progress = BUtil::arrayMerge($progress, $data);
            BCache::i()->save('marketclient_progress', $progress);
        }
        return $progress;
    }

    public function downloadAndInstall($modules, $force = false)
    {
        $progress = $this->progress();
        if (!$force && !empty($progress['status']) && in_array($progress['status'], array('ACTIVE'))) {
            return;
        }

        $configUpdated = false;
        $i = 0;
        $cnt = sizeof($modules);
        $uid = mt_rand();

        $this->progress(array(
            'status' => 'ACTIVE',
            'cnt' => $cnt,
            'cur' => 0,
            'uid' => $uid,
        ), true);
        if (is_string($modules)) {
            $modules = explode(',', $modules);
        }
        foreach ($modules as $modName => $modInfo) {
            $progress = $this->progress();
            if ($progress['uid'] !== $uid) {
                break;
            }
            if ($progress['status'] === 'STOP') {
                $this->progress(array('status' => 'STOPPED'));
                break;
            }
            $i++;

            if (is_numeric($modName)) {
                $modName = $modInfo;
                $modInfo = array('version' => '*');
            } elseif (is_string($modInfo)) {
                $modInfo = array('version' => $modInfo);
            }

            $this->progress(array(
                'cur' => $i,
                'modules' => array(
                    $modName => BLocale::_('[%d/%d] Downloading: %s...', array($i, $cnt, $modName)),
                ),
            ));

            $filename = FCom_MarketClient_RemoteApi::i()->downloadPackage($modName, $modInfo['version']);
            if (!$filename) {
                $this->progress(array(
                    'errors' => array(
                        'Could not download module package file: '.$modName.' ('.$modInfo['version'].')',
                    ),
                ));
                $this->message('Could not download module package file: '.$modName.' ('.$modInfo['version'].')');
                continue;
            }
            $modNameArr = explode('_', $modName);
            $targetDir = BConfig::i()->get('fs/dlc_dir') . '/' . $modNameArr[0] .'/'. $modNameArr[1];
            BUtil::ensureDir($targetDir);

            $this->progress(array(
                'modules' => array(
                    $modName => BLocale::_('[%d/%d] Downloading: %s... Installing...', array($i, $cnt, $modName)),
                ),
            ));

            if (!BUtil::zipExtract($filename, $targetDir)) {
                $this->progress(array(
                    'errors' => array(
                        'Could not extract module package file: '.$modName.' ('.$modInfo['version'].')',
                    ),
                ));
                $this->message('Could not extract module package file: '.$modName.' ('.$modInfo['version'].')');
                continue;
            }
            if (!empty($modInfo['enable'])) {
                $configUpdated = true;
                BConfig::i()->set('module_run_levels/FCom_Core/'.$modName, 'REQUESTED', false, true);
            }
            $this->progress(array(
                'modules' => array(
                    $modName => BLocale::_('[%d/%d] Downloading: %s... Installing... DONE', array($i, $cnt, $modName)),
                ),
            ));
        }
        $this->progress(array('status' => 'DONE'));
        if ($configUpdated) {
            FCom_Core_Main::i()->writeConfigFiles();
        }
    }

    public function stopDownloading()
    {
        $this->progress(array('status' => 'STOP'));
        return $this;
    }
}
