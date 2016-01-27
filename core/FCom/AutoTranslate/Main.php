<?php

/**
 * Class FCom_AutoTranslate_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
*/
class FCom_AutoTranslate_Main extends BClass
{
    protected $_cacheFile;
    protected $_requestCache = [];
    protected $_requestLang;
    protected $_apiUrl = 'https://www.googleapis.com/language/translate/v2';
    protected $_apiKey;
    protected $_immediate;

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/FCom_AutoTranslate' => BLocale::i()->_('Auto Translate Settings'),
        ]);

        $this->_apiKey = $this->BConfig->get('modules/FCom_AutoTranslate/google_api_key');
        if ($this->_apiKey) {
            $this->_requestLang = $this->BLocale->getCurrentLanguage();
            $this->_cacheFile = $this->BConfig->get('fs/cache_dir') . "/auto_translations-{$this->_requestLang}.json";
            $this->_immediate = $this->BConfig->get('modules/FCom_AutoTranslate/translate_immediately') == 1;
            $this->BLocale
                ->addTranslationsFile($this->_cacheFile)
                ->addCustomTranslator(__CLASS__, [$this, 'translateCallback']);
        }
    }

    public function translateCallback($string, $language = null, $module = null)
    {
        if (!$this->_requestLang) {
            $this->_requestLang = $this->BLocale->getCurrentLanguage();
        }

        $lang = $language ?: $this->_requestLang;
        $string1 = preg_replace_callback('#(%\S+)#', function($a) { return '<t v="' . htmlspecialchars($a[1]) . '"/>'; }, $string);

        if ($this->_immediate) {

            $translated = $this->callGoogleTranslateApi($string1, $lang);

            $translated = htmlspecialchars_decode($translated, ENT_QUOTES);
            #$translated = preg_replace_callback('/&#(\d+);/m', function($a) { return chr($a); }, $translated);
            #$translated = preg_replace_callback('/&#x([a-fA-F0-9]+);/m', function($a) { return chr('0x' . $a); }, $translated);
            $translated = preg_replace_callback('#<t v="([^"]+)"/>#', function($a) { return htmlspecialchars_decode($a[1]); }, $translated);
            $this->_requestCache[$string] = $translated;
            return $translated;
        } else {
            $this->_requestCache[$string] = $string1;
            return $string;
        }
    }

    public function onShutdown($args)
    {
        if (!$this->_requestCache) {
            return;
        }
        if (!$this->_immediate) {
            $query = array_values($this->_requestCache);
            $result = $this->callGoogleTranslateApi($query);
            foreach ($this->_requestCache as $string => $string1) {
                if (empty($result[$string1])) {
                    $this->_requestCache[$string] = null;
                    continue;
                }
                $translated = htmlspecialchars_decode($result[$string1], ENT_QUOTES);
                $translated = preg_replace('#<t v="([^"]+)"/>#', '$1', $translated);
                // $translated = preg_replace_callback('#<t v="([^"]+)"/>#', function($a) {
                //     return htmlspecialchars_decode($a[1]);
                // }, $translated);
                $this->_requestCache[$string] = $translated;
            }
        }
        if (file_exists($this->_cacheFile)) {
            $source = file_get_contents($this->_cacheFile);
            $data = $this->BUtil->fromJson($source);
            if (is_array($data)) {
                $data = array_merge($data, $this->_requestCache);
            } else {
                $data = $this->_requestCache;
            }
        } else {
            $data = $this->_requestCache;
        }
        if (is_array($data)) {
            $result = $this->BUtil->toJson($data);
            file_put_contents($this->_cacheFile, $result);
        }
    }

    public function callGoogleTranslateApi($query, $targetLanguage = null, $sourceLanguage = null)
    {
        if (!$query || !$this->_apiKey) {
            return $query;
        }
        if (!$sourceLanguage) {
            $sourceLanguage = 'en';
        }
        if (!$targetLanguage) {
            $targetLanguage = $this->_requestLang;
        }
        if ($sourceLanguage === $targetLanguage) {
            return $query;
        }

        if (is_array($query) && sizeof($query) > 100) {
            $chunks = array_chunk($query, 100);
            $result = [];
            foreach ($chunks as $chunk) {
                $result = array_merge($result, $this->callGoogleTranslateApi($chunk, $targetLanguage, $sourceLanguage));
            }
            return $result;
        }

        $requestUrl = $this->BUtil->setUrlQuery($this->_apiUrl, [
            'key' => $this->_apiKey,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
        ]);
        foreach ((array)$query as $q) {
            $requestUrl .= '&q=' . urlencode($q);
        }
        $response = $this->BUtil->remoteHttp('GET', $requestUrl, [], ['referer: ' . $this->BRequest->currentUrl()],
            ['curl' => true]);
        $status = $this->BUtil->lastRemoteHttpInfo();
        #var_dump($this->BUtil->remoteHttp('GET', 'http://ipinfo.io'));
        if ($status['headers']['http']['code'] != 200) {
            $this->BDebug->notice('Google Translate API error (1): ' . $requestUrl . "\n" . print_r($response, 1) . "\n"
                                  . print_r($status, 1));
            return false;
        }
        $apiResult = $this->BUtil->fromJson($response);
        if (empty($apiResult['data']['translations'])) {
            $this->BDebug->notice('Google Translate API error (2): ' . $requestUrl . "\n" . print_r($response, 1) . "\n"
                                  . print_r($status, 1));
            return false;
        }
        $translations = $apiResult['data']['translations'];
        if (is_string($query)) {
            return $translations[0]['translatedText'];
        }
        $result = [];
        foreach ($query as $i => $text) {
            $result[$text] = $translations[$i]['translatedText'];
        }
        return $result;
    }
}
