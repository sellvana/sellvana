<?php

class FCom_Market_Api extends BClass
{
    public static function bootstrap()
    {
        //BConfig::i()->get('FCom_Market/market_url');
    }

    public function getAllModules()
    {
        //$fulleronUrl = BConfig::i()->get('FCom_Market/market_url');
        $fulleronUrl = 'http://fulleron.home/marketserver/modules';
        if (empty($fulleronUrl)) {
            return false;
        }

        $data = BUtil::fromJson(file_get_contents($fulleronUrl));
        return $data;
    }

    public function download($moduleName)
    {
        //$fulleronUrl = BConfig::i()->get('FCom_Market/market_url');
        $fulleronUrl = 'http://fulleron.home/marketserver/download?id='.$moduleName;
        //$fulleronUrl = 'http://fulleron.home/download/'.$moduleName.'.zip';

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

    public function extract($filename)
    {
        $dir = BConfig::i()->get('fs/fcom_root_dir').'/market-files';

        $zip = new ZipArchive;
        $res = $zip->open($filename);
        if ($res === TRUE) {
            $zip->extractTo($dir);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }
}