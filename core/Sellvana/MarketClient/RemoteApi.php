<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MarketClient_RemoteApi
 */
final class Sellvana_MarketClient_RemoteApi extends BClass
{
    /**
     * @var string
     */
    protected static $_modulesVersionsCacheKey = 'marketclient_modules_versions';

    /**
     * @var string
     */
    protected $_apiUrl = 'https://market.sellvana.com/';
    #protected $_apiUrl = 'http://127.0.0.1/sellvana/';

    /**
     * @param string $path
     * @param array $params
     * @return string
     */
    public function getUrl($path = '', $params = [])
    {
        $url = $this->_apiUrl;
        $url .= ltrim($path, '/');
        if ($params) {
            $url = $this->BUtil->setUrlQuery($url, $params);
        }
        return $url;
    }

    /**
     * @param string $data
     * @return array|mixed
     */
    public function setupConnection($data = null)
    {
        $siteKey = $this->BConfig->get('modules/Sellvana_MarketClient/site_key');
        $redirect = $this->BRequest->get('redirect_to');
        if (!$this->BRequest->isUrlLocal($redirect)) {
            $redirect = '';
        }

        $url = $this->getUrl('api/v1/market/site/connect', [
            'admin_url' => $this->BApp->adminHref(),
            'retry_url' => $this->BApp->adminHref('marketclient/site/connect'),
            'redirect_to' => $redirect,
            'site_key' => $siteKey,

            'email' => !empty($data['email']) ? $data['email'] : null,
            'firstname' => !empty($data['firstname']) ? $data['firstname'] : null,
            'lastname' => !empty($data['lastname']) ? $data['lastname'] : null,
            'role' => !empty($data['role']) ? $data['role'] : null,
        ]);
        $response = $this->BUtil->remoteHttp('GET', $url);
        $result = $this->BUtil->fromJson($response);
        if (!empty($result['site_key'])) {
            $this->BConfig->set('modules/Sellvana_MarketClient/site_key', $result['site_key'], false, true);
            $this->BConfig->writeConfigFiles('local');
        }
        return $result;
    }

    /**
     * @param $modules
     * @param bool $resetCache
     * @return array
     * @throws BException
     */
    public function getModulesVersions($modules, $resetCache = false)
    {
        $cached = $this->BCache->load(static::$_modulesVersionsCacheKey);
        if ($cached && true === $modules && !$resetCache) {
            return $cached;
        }

        if (true === $modules) {
            $modules = array_keys($this->BModuleRegistry->getAllModules());
        } elseif (is_string($modules)) {
            $modules = explode(',', $modules);
        }

        $siteKey = $this->BConfig->get('modules/Sellvana_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/version', [
            'mod_name' => join(',', $modules),
            'site_key' => $siteKey,
        ]);
        $response = $this->BUtil->remoteHttp("GET", $url);
        $remoteModResult = $this->BUtil->fromJson($response);
        if (!empty($remoteModResult['error'])) {
            $this->BCache->delete(static::$_modulesVersionsCacheKey);
            throw new BException($remoteModResult['message']);
        }
        if (empty($remoteModResult['modules'])) {
            //throw new BException('Unable to retrieve marketplace modules information');
            return []; //TODO: proper notifications and errors handling
        }
        foreach ($remoteModResult['modules'] as $remoteModName => $remoteMod) {
            if ($remoteMod && empty($remoteMod['name'])) {
                $remoteMod['name'] = $remoteModName;
            }
            if (!empty($remoteMod['status']) && $remoteMod['status'] === 'mine') {
                $localMod = $this->BApp->m($remoteModName);
                if (!empty($remoteMod['channels'][$localMod->channel])) {
                    $remoteChannelVer = $remoteMod['channels'][$localMod->channel]['version_uploaded'];
                    $remoteMod['can_update'] = version_compare($remoteChannelVer, $localMod->version, '<');
                } else {
                    $remoteMod['can_update'] = false;
                }
            }
            $cached[$remoteModName] = $remoteMod;
        }
        if (!empty($cached)) {
            $this->BCache->save(static::$_modulesVersionsCacheKey, $cached, 86400);
        }
        $result = [];
        foreach ($modules as $remoteModName) {
            $result[$remoteModName] = $cached[$remoteModName];
        }
        return $result;
    }

    /**
     * @param $modules
     * @return mixed
     * @throws BException
     */
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

    /**
     * @param $modName
     * @return array|mixed
     */
    public function createModule($modName)
    {
        $siteKey = $this->BConfig->get('modules/Sellvana_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/create');
        $data = [
            'site_key' => $siteKey,
            'mod_name' => $modName,
        ];
        $response = $this->BUtil->remoteHttp('POST', $url, $data);
        return $this->BUtil->fromJson($response);
    }

    /**
     * @param $moduleName
     * @return array|mixed
     * @throws BException
     */
    public function uploadPackage($moduleName)
    {
        $mod = $this->BModuleRegistry->module($moduleName);
        if (!$mod) {
            return ['error' => true, 'message' => 'Invalid package: ' . $moduleName];
        }
        $packageDir = $this->BApp->storageRandomDir() . '/marketclient/upload';
        $this->BUtil->ensureDir($packageDir);
        $packageFilename = "{$packageDir}/{$moduleName}-{$mod->version}-{$mod->getChannel()}.zip";
        @unlink($packageFilename);
        $ignorePattern = !empty($mod->package['ignore_files']) ? $mod->package['ignore_files'] : null;
        $this->BUtil->zipCreateFromDir($packageFilename, $mod->root_dir, $ignorePattern);
        $siteKey = $this->BConfig->get('modules/Sellvana_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/upload');
        $data = [
            'site_key' => $siteKey,
            'mod_name' => $moduleName,
            'package_zip' => '@' . $packageFilename,
        ];
        $response = $this->BUtil->remoteHttp('POST', $url, $data);
#echo "<pre>"; var_dump($response); exit;
        $this->BCache->delete(static::$_modulesVersionsCacheKey);
        return $this->BUtil->fromJson($response);
    }

    /**
     * @param $moduleName
     * @param null $version
     * @param null $channel
     * @return string
     * @throws BException
     */
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
        $reqInfo = $this->BUtil->lastRemoteHttpInfo();
        if (!$response) {
            throw new BException("Problem downloading the package ({$moduleName}) <pre>{$url}: " . print_r($reqInfo, 1) . '</pre>');
        }
        $dir = $this->BApp->storageRandomDir() . '/marketclient/download';
        $this->BUtil->ensureDir($dir);
        if (!is_writable($dir)) {
            throw new BException("Problem with write permissions ({$dir})");
        }

        $filename = $moduleName . '.zip';
        if (empty($reqInfo['headers']['content-disposition'])) {
            var_dump($reqInfo);
            var_dump($response);
            exit;
        }
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
