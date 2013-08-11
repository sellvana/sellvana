<?php

final class FCom_MarketClient_RemoteApi extends BClass
{
    public function getUrl($path = '', $params = array())
    {
        $config = BConfig::i()->get('modules/FCom_MarketClient');
        $timestamp = microtime(true);
        $id = !empty($config['id']) ? $config['id'] : null;
        $secret = !empty($config['secret']) ? $config['secret'] : null;
        if ($id && $secret) {
            $hash = sha1($id . $secret . $timestamp);
        } else {
            $hash = '';
        }
        #$url = !empty($config['market_url']) ? $config['market_url'] : 'http://fulleron.com';
        $url = 'http://127.0.0.1/fulleron/'; # 'https://fulleron.com/';
        $url .= ltrim($path, '/') . "?id={$id}&ts={$timestamp}&hash={$hash}";
        if ($params) {
            $url = BUtil::setUrlQuery($url, $params);
        }
        return $url;
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
