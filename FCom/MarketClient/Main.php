<?php

class FCom_MarketClient_Main extends BClass
{
    public function installFiles($modName, $origZipFile)
    {
        if (!preg_match('#^([A-Za-z0-9]+)_([A-Za-z0-9]+)$#', $modName, $match)) {
            throw new BException('Invalid module name: ' . $modName);
        }

        if (!BUtil::isPathAbsolute($origZipFile)) {
            $origZipFile = BConfig::i()->get('fs/storage_dir') . '/dlc/packages/' . $origZipFile;
        }

        $targetDir = BConfig::i()->get('fs/dlc_dir') . '/' . $match[1] . '/' . $match[2];
        BUtil::ensureDir($targetDir);

        $ftpConf = BConfig::i()->get('modules/FCom_MarketClient/ftp');
        if (!empty($ftpConf['enabled'])) {
            $modulePath = dirname($moduleFile).'/'.$modName;
            $res = FCom_MarketClient_Main::i()->extract($origZipFile, $modulePath);
            //copy modulePath by FTP to marketPath
            if (!$res) {
                throw new BException("Permissions denied to write into storage dir: ".$modulePath);
            }
            if (empty($ftpConf['port'])) {
                $ftpConf['port'] = $ftpConf['type'] =='ftp' ? 21 : 22;
            }
            $ftpClient = new BFtpClient($ftpConf);
            $errors = $ftpClient->upload($modulePath, $targetDir);
            if ($errors) {
                throw new BException(join("\n", $errors));
            }
        } else {
            $res = FCom_MarketClient_Main::i()->extract($moduleFile, $targetDir);
        }
        return $res;
    }
}
