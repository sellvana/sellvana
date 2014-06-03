<?php defined('BUCKYBALL_ROOT_DIR') || die();

final class FCom_MarketClient_RemoteApi extends BClass
{
    protected static $_modulesVersionsCacheKey = 'marketclient_modules_versions';

    #protected $_apiUrl = 'https://market.sellvana.com/';
    protected $_apiUrl = 'http://market.sellvana.com/';

    public function getUrl($path = '', $params = [])
    {
        $url = $this->_apiUrl;
        $url .= ltrim($path, '/');
        if ($params) {
            $url = $this->BUtil->setUrlQuery($url, $params);
        }
        return $url;
    }

    public function setupConnection()
    {
        $siteKey = $this->BConfig->get('modules/FCom_MarketClient/site_key');
        $redirect = $this->BRequest->get('redirect_to');
        if (!$r->isUrlLocal($redirect)) {
            $redirect = '';
        }

        $url = $this->getUrl('api/v1/market/site/connect', [
            'admin_url' => $this->BApp->href(),
            'retry_url' => $this->BApp->href('marketclient/site/connect'),
            'redirect_to' => $redirect,
            'site_key' => $siteKey,
        ]);
        $response = $this->BUtil->remoteHttp('GET', $url);
        $result = $this->BUtil->fromJson($response);
        if (!empty($result['site_key'])) {
            $this->BConfig->set('modules/FCom_MarketClient/site_key', $result['site_key'], false, true);
            $this->FCom_Core_Main->writeConfigFiles('local');
        }
        return $result;
    }

    public function getModulesVersions($modules, $resetCache = false)
    {
        $cached = BCache::i()->load(static::$_modulesVersionsCacheKey);
        if ($cached && true === $modules && !$resetCache) {
            return $cached;
        }

        if (true === $modules) {
            $modules = array_keys($this->BModuleRegistry->getAllModules());
        } elseif (is_string($modules)) {
            $modules = explode(',', $modules);
        }

        $siteKey = $this->BConfig->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/version', [
            'mod_name' => join(',', $modules),
            'site_key' => $siteKey,
        ]);
        $response = $this->BUtil->remoteHttp("GET", $url);
        $modResult = $this->BUtil->fromJson($response);
        if (!empty($modResult['error'])) {
            BCache::i()->delete(static::$_modulesVersionsCacheKey);
            throw new BException($modResult['message']);
        }
        foreach ($modResult['modules'] as $modName => $mod) {
            if ($mod && empty($mod['name'])) {
                $mod['name'] = $modName;
            }
            if (!empty($mod['status']) && $mod['status'] === 'mine') {
                $localMod = $this->BApp->m($modName);
                $remChannelVer = $mod['channels'][$localMod->channel]['version_uploaded'];
                $mod['can_update'] = version_compare($remChannelVer, $localMod->version, '<');
            }
            $cached[$modName] = $mod;
        }
        if (!empty($cached)) {
            BCache::i()->save(static::$_modulesVersionsCacheKey, $cached, 86400);
        }
        $result = [];
        foreach ($modules as $modName) {
            $result[$modName] = $cached[$modName];
        }
        return $result;
    }

    public function getModuleInstallInfo($modules)
    {
        $url = $this->getUrl('api/v1/market/module/install_info', [
            'mod_name' => $modules,
        ]);
        $response = $this->BUtil->remoteHttp("GET", $url);
#var_dump($response); exit;
        $result = $this->BUtil->fromJson($response);
        if (!empty($result['error'])) {
            throw new BException($result['message']);
        }
        $modules = $result['modules'];
        foreach ($modules as $modName => &$modInfo) {
            $localMod = $this->BApp->m($modName);
            $modInfo['local_channel'] = $localMod ? $localMod->channel : null;
            $modInfo['local_version'] = $localMod ? $localMod->version : null;
            if ($localMod) {
                if ($modInfo['status'] === 'dependency') {
                    if (version_compare($localMod->version, $modInfo['version'], '<')) {
                        $modInfo['status'] = 'upgrade';
                    } else {
                        #unset($result[$modName]);
                        $modInfo['status'] = 'latest';
                    }
                }
            } else {
                $modInfo['status'] = 'install';
            }
        }
        unset($modInfo);
        return $modules;
    }

    public function createModule($modName)
    {
        $siteKey = $this->BConfig->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/create');
        $data = [
            'site_key' => $siteKey,
            'mod_name' => $modName,
        ];
        $response = $this->BUtil->remoteHttp('POST', $url, $data);
        return $this->BUtil->fromJson($response);
    }

    public function uploadPackage($moduleName)
    {
        $mod = $this->BModuleRegistry->module($moduleName);
        if (!$mod) {
            return ['error' => true, 'message' => 'Invalid package: ' . $moduleName];
        }
        $packageDir = $this->BApp->storageRandomDir() . '/marketclient/upload';
        $this->BUtil->ensureDir($packageDir);
        $packageFilename = "{$packageDir}/{$moduleName}-{$mod->version}.zip";
        @unlink($packageFilename);
        $this->BUtil->zipCreateFromDir($packageFilename, $mod->root_dir);
        $siteKey = $this->BConfig->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/upload');
        $data = [
            'site_key' => $siteKey,
            'mod_name' => $moduleName,
            'package_zip' => '@' . $packageFilename,
        ];
        $response = $this->BUtil->remoteHttp('POST', $url, $data);
#echo "<pre>"; var_dump($response); exit;
        BCache::i()->delete(static::$_modulesVersionsCacheKey);
        return $this->BUtil->fromJson($response);
    }

    public function downloadPackage($moduleName, $version = null, $channel = null)
    {
        if ($version === '*') {
            $version = null;
        }
        if ($channel === '*') {
            $channel = null;
        }
        $url = $this->getUrl('api/v1/market/module/download', [
            'mod_name' => $moduleName,
            'version' => $version,
            'channel' => $channel,
        ]);
        $response = $this->BUtil->remoteHttp("GET", $url);
        if (!$response) {
            throw new BException("Problem downloading the package ({$moduleName})");
        }
        $dir = $this->BApp->storageRandomDir() . '/marketclient/download';
        $this->BUtil->ensureDir($dir);
        if (!is_writable($dir)) {
            throw new BException("Problem with write permissions ({$dir})");
        }

        $filename = $moduleName . '.zip';
        $reqInfo = $this->BUtil->lastRemoteHttpInfo();
        if (preg_match('#;\s*filename=(.*)$#i', $reqInfo['headers']['content-disposition'], $m)) {
            $filename = $m[1];
        }
        $filepath = $dir . '/' . $filename;
        if (file_put_contents($filepath, $response)) {
            return $filepath;
        } else {
            throw new BException("Problem with write permissions ({$filepath})");
        }
    }
}
