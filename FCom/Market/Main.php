<?php

class FCom_Market_Main extends BClass
{
    private $error='';

    public function getSsoUrl()
    {
        return BConfig::i()->get('modules/FCom_Market/market_url') . '/market/sso?'.$this->getTokenUrl();
    }

    public function getModules($modules)
    {
        $fulleronUrl = BConfig::i()->get('modules/FCom_Market/market_url') . '/market/api/list?'.$this->getTokenUrl();
        if (empty($fulleronUrl)) {
            return false;
        }
        if (!empty($modules)) {
            $fulleronUrl .= "&modules=".BUtil::toJson($modules);
        }

        $response = $this->apiCall("GET", $fulleronUrl);

        return BUtil::fromJson($response->response);
    }

    public function getMyModules()
    {
        $fulleronUrl = BConfig::i()->get('modules/FCom_Market/market_url')
                . '/market/api/mylist'.'?'.$this->getTokenUrl();
        if (empty($fulleronUrl)) {
            return false;
        }

        $response = $this->apiCall("GET", $fulleronUrl);

        return BUtil::fromJson($response->response);
    }

    public function getModuleById($moduleId)
    {
        $fulleronUrl = BConfig::i()->get('modules/FCom_Market/market_url').
                '/market/api/info?modid='.$moduleId.'&'.$this->getTokenUrl();
        if (empty($fulleronUrl)) {
            return false;
        }

        $response = $this->apiCall("GET", $fulleronUrl);

        return BUtil::fromJson($response->response);

        //$data = BUtil::fromJson(file_get_contents($fulleronUrl));
        //return $data;
    }

    public function download($moduleName)
    {
        $fulleronUrl = BConfig::i()->get('modules/FCom_Market/market_url') .
                '/market/api/download?mod_name='.$moduleName.'&'.$this->getTokenUrl();

        $storage = BConfig::i()->get('fs/storage_dir');
        $response = $this->apiCall("GET", $fulleronUrl);

        $data = $response->response;
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
            } else {
                $this->error = "Can't extract zip archive: ".$filename . " to ".$dir;
            }
        } else {
            $this->error = "Can't open zip archive: ".$filename;
        }

        return false;
    }

    public function getErrors()
    {
        return $this->error;
    }

    public function apiCall($method, $url, $params = array(), $http_options = array())
    {
        if ($method == "GET" || $method == "DELETE") {
            if (!empty($params)) {
                $args = http_build_query($params);

                // remove the php special encoding of parameters
                // see http://www.php.net/manual/en/function.http-build-query.php#78603
                $args = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $args);

                $url .= '?' . $args;
            }
            $args = '';
        } else {
            $args = BUtil::toJson($params);
        }

        $session = curl_init($url);
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, $method); // Tell curl to use HTTP method of choice
        if ($args) {
            curl_setopt($session, CURLOPT_POSTFIELDS, $args); // Tell curl that this is the body of the POST
        }
        curl_setopt($session, CURLOPT_HEADER, false); // Tell curl not to return headers
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true); // Tell curl to return the response
        curl_setopt($session, CURLOPT_HTTPHEADER, array('Expect:')); //Fixes the HTTP/1.1 417 Expectation Failed

        foreach ($http_options as $curlopt => $value) {
            curl_setopt($session, $curlopt, $value);
        }
//echo $url;
        $response = curl_exec($session);

        $http_code = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        if ($http_code >= 200 && $http_code < 300) {
            $objRes = new stdClass();
            $objRes->response = $response;
            $objRes->code = $http_code;
            return $objRes;
        }

        var_dump($http_code, $response); exit;
        throw new Exception($response, $http_code);
    }

    private function getTokenUrl()
    {
        $config = BConfig::i()->get('modules/FCom_Market');
        $timestamp = time();
        $id = !empty($config['id']) ? $config['id'] : null;
        $salt = !empty($config['salt']) ? $config['salt'] : null;
        if ($id && $salt) {
            $token = sha1($id.$salt.$timestamp);
        } else {
            $token = null;
        }
        $str = 'id='.$id.'&token='.$token.'&ts='.$timestamp;
        return $str;
    }


}