<?php

final class FCom_MarketClient_RemoteApi extends BClass
{
    public function getUrl($path = '', $params = array())
    {
        $url = 'http://127.0.0.1/fulleron/'; # 'https://fulleron.com/';
        $url .= ltrim($path, '/');
        if ($params) {
            $url = BUtil::setUrlQuery($url, $params);
        }
        return $url;
    }

    public function requestSiteNonce()
    {
        $siteKey = BConfig::i()->get('module/FCom_MarketClient/site_key');
        $url = $this->getUrl('api/v1/market/site/nonce', array(
            'admin_url' => BApp::href(),
            'site_key' => $siteKey,
            'auto_login' => BConfig::i()->get('module/FCom_MarketClient/auto_login'),
        ));
        $response = BUtil::remoteHttp('GET', $url);
        return BUtil::fromJson($response);
    }

    public function requestSiteKey($nonce)
    {
        $url = $this->getUrl('api/v1/market/site/key', array(
            'nonce' => $nonce,
        ));
        $response = BUtil::remoteHttp('GET', $url);
        return BUtil::fromJson($response);
    }

    public function getSsoUrl()
    {
        return  $this->getUrl('market/sso');
    }

    public function getModules($modules)
    {
        $url = $this->getUrl('api/v1/market/module/list', array('modules' => BUtil::toJson($modules)));
        $response = BUtil::remoteHttp("GET", $url);
        return BUtil::fromJson($response);
    }

    public function getMyModules()
    {
        $url = $this->getUrl('api/v1/market/site/modules');
        $response = BUtil::remoteHttp("GET", $url);
        return BUtil::fromJson($response);
    }

    public function getModuleById($moduleId)
    {
        $url =  $this->getUrl('api/v1/market/module/info', array('modid' => $moduleId));
        $response = BUtil::remoteHttp("GET", $url);
        return BUtil::fromJson($response);
    }

    public function downloadPackage($moduleName)
    {
        $url =  $this->getUrl('api/v1/market/module/download', array('mod_name' => $moduleName));
        $data = BUtil::remoteHttp("GET", $fulleronUrl);
        $dir = BConfig::i()->get('fs/storage_dir') . '/dlc/packages';
        BUtil::ensureDir($dir);
        if (!is_writable($dir)) {
            return false;
        }
        $filename = $dir . '/' . $moduleName . '.zip';
        if (file_put_contents($filename, $data)) {
            return $filename;
        } else {
            return false;
        }
    }

}
