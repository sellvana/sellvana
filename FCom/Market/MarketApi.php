<?php

class FCom_Market_MarketApi extends BClass
{
    private $error='';

    public static function bootstrap()
    {
        //BConfig::i()->get('FCom_Market/market_url');
    }

    private function getTokenUrl()
    {
        $config = BConfig::i()->get('modules/FCom_Market');
        $timestamp = time();
        $token = sha1($config['id'].$config['salt'].$timestamp);

        $str = 'id='.$config['id'].'&token='.$token.'&ts='.$timestamp;
        return $str;
    }

    public function getMyModules()
    {
        $fulleronUrl = BConfig::i()->get('modules/FCom_Market/market_url')
                . '/market/module/apimylist'.'?'.$this->getTokenUrl();
        if (empty($fulleronUrl)) {
            return false;
        }

        $data = BUtil::fromJson(file_get_contents($fulleronUrl));
        return $data;
    }

    public function getModuleById($moduleId)
    {
        $fulleronUrl = BConfig::i()->get('modules/FCom_Market/market_url').
                '/market/module/apiinfo?modid='.$moduleId.'&'.$this->getTokenUrl();
        if (empty($fulleronUrl)) {
            return false;
        }

        $data = BUtil::fromJson(file_get_contents($fulleronUrl));
        return $data;
    }

    public function download($moduleName)
    {
        $fulleronUrl = BConfig::i()->get('modules/FCom_Market/market_url') .
                '/market/module/apidownload?modid='.$moduleName.'&'.$this->getTokenUrl();

        $storage = BConfig::i()->get('fs/storage_dir');
        $data = file_get_contents($fulleronUrl);
        $path = $storage.'/dlc/';
        if (!file_exists($path)) {
            mkdir($path);
        }
        if (!is_writable($path)) {
            return false;
        }
        $filename = $path . $moduleName.'.zip';
        file_put_contents($filename, $data);

        return $filename;
    }

    public function extract($filename, $dir)
    {
        if (!class_exists('ZipArchive')) {
            $this->error = "Class ZipArchive doesn't exist";
            return false;
        }
        $zip = new ZipArchive;
        $res = $zip->open($filename);
        if ($res === TRUE) {
            $res = $zip->extractTo($dir);
            $zip->close();
            if ($res) {
                return true;
            }
        }
        $this->error = $zip->getStatusString();
        return false;
    }

    public function getErrors()
    {
        return $this->error;
    }
}