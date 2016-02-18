<?php

/**
 * Class Sellvana_MarketClient_Main
 *
 * @property Sellvana_MarketClient_RemoteApi $Sellvana_MarketClient_RemoteApi
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 */
class Sellvana_MarketClient_Main extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_MarketClient' => 'Market Client Settings',
            'market_client' => 'Market Client',
            'market_client/install' => 'Market Client Install',
            'market_client/publish' => 'Market Client Publish',
        ]);

    }

    /**
     * @param null $data
     * @param bool $reset
     * @return array
     */
    public function progress($data = null, $reset = false)
    {
        $progress = !$reset ? $this->BCache->load('marketclient_progress') : [];
        if (!empty($data)) {
            $progress = $this->BUtil->arrayMerge($progress, $data);
        }
        $this->BCache->save('marketclient_progress', $progress);
        return $progress;
    }

    /**
     * @param array $modules
     * @param bool $force
     * @return array
     * @throws BException
     */
    public function downloadAndInstall($modules, $force = false)
    {
        $progress = $this->progress();
        if (!$force && !empty($progress['status']) && in_array($progress['status'], ['ACTIVE'])) {
            return [
                'error' => true,
                'message' => 'Installation already in progress: ' . $progress['status'],
            ];
        }
        foreach ($modules as $modName => $modInfo) {
            if (!$modInfo || $modInfo === '-'
                || (!empty($modInfo['version']) && ($modInfo['version'] === '' || $modInfo['version'] === '-'))
            ) {
                unset($modules[$modName]);
            }
        }

        $configUpdated = false;
        $i = 0;
        $cnt = sizeof($modules);
        $uid = mt_rand();

        $this->progress([
            'status' => 'ACTIVE',
            'cnt' => $cnt,
            'cur' => 0,
            'uid' => $uid,
        ], true);
        if (is_string($modules)) {
            $modules = explode(',', $modules);
        }
        foreach ($modules as $modName => $modInfo) {
            $progress = $this->progress();
            if ($progress['uid'] !== $uid) {
                break;
            }
            if ($progress['status'] === 'STOP') {
                $this->progress(['status' => 'STOPPED']);
                break;
            }
            $i++;

            if (is_numeric($modName)) {
                $modName = $modInfo;
                $modInfo = ['version' => '*'];
            } elseif (is_string($modInfo)) {
                $modInfo = ['version' => $modInfo];
            }

            $this->progress([
                'cur' => $i,
                'modules' => [
                    $modName => $this->BLocale->_('[%d/%d] Downloading: %s...', [$i, $cnt, $modName]),
                ],
            ]);

            $filename = $this->Sellvana_MarketClient_RemoteApi->downloadPackage($modName, $modInfo['version']);
            if (!$filename) {
                $this->progress([
                    'errors' => [
                        'Could not download module package file: ' . $modName . ' (' . $modInfo['version'] . ')',
                    ],
                ]);
                $this->message('Could not download module package file: ' . $modName . ' (' . $modInfo['version'] . ')');
                continue;
            }
            $modNameArr = explode('_', $modName);
            $targetDir = $this->BConfig->get('fs/dlc_dir') . '/' . $modNameArr[0] . '/' . $modNameArr[1];
            $this->BUtil->ensureDir($targetDir);

            $this->progress([
                'modules' => [
                    $modName => $this->BLocale->_('[%d/%d] Downloading: %s... Installing...', [$i, $cnt, $modName]),
                ],
            ]);

            /**
             * TODO: implement the following process:
             * 1. Unpack files from module package into tmp folder
             * 2. Validate CRC for unpacked files
             * 2a. If there's a problem, abort and error
             * 3. Backup existing module files
             * 4. Copy new files from tmp folder into dlc module location, overwriting old files
             * 5. Check that files in dlc module location matching files in tmp folder
             * 5a. If there's a problem, restore files from backup, abort and error
             * 6. Delete old files from dlc module location that are not in the new package
             * 7. Clear cache and run migrations
             */
            if (!$this->BUtil->zipExtract($filename, $targetDir)) {
                $this->progress([
                    'errors' => [
                        'Could not extract module package file: ' . $modName . ' (' . $modInfo['version'] . ')',
                    ],
                ]);
                $this->message('Could not extract module package file: ' . $modName . ' (' . $modInfo['version'] . ')');
                continue;
            }
            if (!empty($modInfo['enable'])) {
                $configUpdated = true;
                $this->BConfig->set('module_run_levels/FCom_Core/' . $modName, 'REQUESTED', false, true);
            }
            $this->progress([
                'modules' => [
                    $modName => $this->BLocale->_('[%d/%d] Downloading: %s... Installing... DONE', [$i, $cnt, $modName]),
                ],
            ]);
        }
        $this->progress(['status' => 'DONE']);
        if ($configUpdated) {
            $this->BConfig->writeConfigFiles();
        }
        return [
            'success' => true,
        ];
    }

    /**
     * @return $this
     */
    public function stopDownloading()
    {
        $this->progress(['status' => 'STOP']);
        return $this;
    }

    public function onGetHeaderNotifications($args)
    {
        if (!$this->BConfig->get('modules/Sellvana_MarketClient/auto_check_enable')) {
            return;
        }

        $updates = $this->Sellvana_MarketClient_RemoteApi->fetchUpdatesFeed();

        if (!empty($updates['items'])) {
            foreach ($updates['items'] as $item) {
                //TODO: make sure correct structure
                $item['feed'] = 'remote';
                $args['items'][] = $item;
            }
        }
    }

    public function onInstallStep3Post($args)
    {
        if (empty($args['data']['account'])) {
            return;
        }
        $this->Sellvana_MarketClient_RemoteApi->setupConnection($args['data']['account']);
    }
}
