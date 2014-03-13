<?php

final class FCom_MarketClient_RemoteApi extends BClass
{
    protected $_apiUrl = 'https://www.sellvana.com/';

    public function getUrl($path = '', $params = array())
    {
        $url = $this->_apiUrl;
        $url .= ltrim($path, '/');
        if ($params) {
            $url = BUtil::setUrlQuery($url, $params);
        }
        return $url;
    }

    public function setupConnection()
    {
        $siteKey = BConfig::i()->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/site/connect', array(
            'admin_url' => BApp::href(),
            'retry_url' => BApp::href('marketclient/site/connect'),
            'site_key' => $siteKey,
        ));
        $response = BUtil::remoteHttp('GET', $url);
        $result = BUtil::fromJson($response);
        if (!empty($result['site_key'])) {
            BConfig::i()->set('modules/FCom_MarketClient/site_key', $result['site_key'], false, true);
            FCom_Core_Main::i()->writeConfigFiles('local');
        }
        return $result;
    }

    public function getModulesVersions($modules)
    {
        $url = $this->getUrl('api/v1/market/module/version', array(
            'mod_name' => $modules,
        ));
        $response = BUtil::remoteHttp("GET", $url);
        return BUtil::fromJson($response);
    }

    public function getModuleInstallInfo($modules)
    {
        $url = $this->getUrl('api/v1/market/module/install_info', array(
            'mod_name' => $modules,
        ));
        $response = BUtil::remoteHttp("GET", $url);
        return BUtil::fromJson($response);
    }

    public function downloadPackage($moduleName, $version = null)
    {
        $url =  $this->getUrl('market/download/'.$moduleName.($version ? '/'.$version : ''));
        $data = BUtil::remoteHttp("GET", $url);
        $dir = BConfig::i()->get('fs/storage_dir') . '/marketclient/download';
        BUtil::ensureDir($dir);
        if (!is_writable($dir)) {
            return false;
        }

        $filename = $dir . '/' . $moduleName . '.zip';
        $reqInfo = BUtil::lastRemoteHttpInfo();
        if (preg_match('#;\s*filename=(.*)$#i', $reqInfo['headers']['content-disposition'], $m)) {
            $filename = $m[1];
        }

        if (file_put_contents($filename, $data)) {
            return $filename;
        } else {
            return false;
        }
    }

    public function publishModule($data)
    {
        $siteKey = BConfig::i()->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/publish', array(
            'site_key' => $siteKey,
        ));
        $response = BUtil::remoteHttp('POST', $url, $data);
        return BUtil::fromJson($response);
    }

    public function uploadPackage($moduleName)
    {
        $mod = BModuleRegistry::i()->module($moduleName);
        $packageDir = BConfig::i()->get('fs/storage_dir') . '/marketclient/upload';
        BUtil::ensureDir($packageDir);
        $packageFilename = "{$packageDir}/{$moduleName}-{$mod->version}.zip";
        BUtil::zipCreateFromDir($packageFilename, $mod->root_dir);
        $siteKey = BConfig::i()->get('modules/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/module/upload', array(
            'mod_name' => $moduleName,
            'site_key' => $siteKey,
        ));
        $data = array(
            'package_zip' => '@'.$packageFilename,
        );
        $response = BUtil::remoteHttp('POST', $url, $data);
#BDebug::dump($response); exit;
        return BUtil::fromJson($response);
    }
}
