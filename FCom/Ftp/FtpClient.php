<?php


class FCom_Ftp_FtpClient extends BClass
{
    protected $_ftpDirMode = 0775;
    protected $_ftpFileMode = 0664;

    public function ftpUpload($from, $to)
    {
        if (!extension_loaded('ftp')) {
            new BException('FTP PHP extension is not installed');
        }
        $conf = BConfig::i()->get('modules/FCom_Market/ftp');
        $conf['port'] = $conf['type'] =='ftp' ? 21 : 22;
        if (!($conn = ftp_connect($conf['hostname'], $conf['port']))) {
            throw new BException('Could not connect to FTP host');
        }

        $password = $conf['password'];

        if (!@ftp_login($conn, $conf['username'], $password)) {
            ftp_close($conn);
            throw new BException('Could not login to FTP host');
        }

        if (!ftp_chdir($conn, $to)) {
            ftp_close($conn);
            throw new BException('Could not navigate to '. $to);
        }

        $errors = $this->ftpUploadDir($conn, $from.'/');
        ftp_close($conn);

        return $errors;
    }

    public function ftpUploadDir($conn, $source, $ftpPath='')
    {
        $errors = array();
        $dir = opendir($source);
        while ($file = readdir($dir)) {
            if ($file=='.' || $file=="..") {
                continue;
            }

            if (!is_dir($source.$file)) {
                if (@ftp_put($conn, $file, $source.$file, FTP_BINARY)) {
                    // all is good
                    #ftp_chmod($conn, $this->_ftpFileMode, $file);
                } else {
                    $errors[] = ftp_pwd($conn).'/'.$file;
                }
                continue;
            }
            if (@ftp_chdir($conn, $file)) {
                // all is good
            } elseif (@ftp_mkdir($conn, $file)) {
                ftp_chmod($conn, $this->_ftpDirMode, $file);
                ftp_chdir($conn, $file);
            } else {
                $errors[] = ftp_pwd($conn).'/'.$file.'/';
                continue;
            }
            $errors += $this->ftpUploadDir($conn, $source.$file.'/', $ftpPath.$file.'/');
            ftp_chdir($conn, '..');
        }
        return $errors;
    }
}