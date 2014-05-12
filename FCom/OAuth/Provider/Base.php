<?php

class FCom_OAuth_Provider_Base extends BClass
{
    public function loginAction()
    {
        $this->getRequestToken();
        return $this->getAuthUrl();
    }

    public function callbackAction()
    {
        $this->getAuthToken();
        $this->getAccessToken();
        return $this;
    }

    protected function _getReqFields($stage, $providerInfo)
    {
        $reqInfo = $providerInfo[$stage];
        if (!is_array($reqInfo)) {
            $f = [];
        } else {
            $f = !empty($reqInfo['req_fields']) ? $reqInfo['req_fields'] : [];
        }
        $f['consumer_key'] = !empty($f['consumer_key']) ? $f['consumer_key'] : 'oauth_consumer_key';
        $f['nonce'] = !empty($f['nonce']) ? $f['nonce'] : 'oauth_nonce';
        $f['callback'] = !empty($f['callback']) ? $f['callback'] : 'oauth_callback';
        return $f;
    }

    protected function _callV1($stage)
    {
        $hlp = FCom_OAuth_Main::i();
        $providerName = $hlp->getProvider();
        $providerInfo = $hlp->getProviderInfo($providerName);
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $consumerConf = BConfig::i()->get('modules/FCom_OAuth/' . $providerName);
        if (empty($consumerConf['consumer_key']) || empty($consumerConf['consumer_secret'])) {
            throw new BException('Missing consumer key or secret for ' . $providerName);
        }

        $reqInfo = $providerInfo[$stage];
        if (!is_array($reqInfo)) {
            $reqInfo = ['url' => $reqInfo];
        }
        $url = $reqInfo['url'];
        $method = !empty($reqInfo['method']) ? $reqInfo['method'] : 'POST';
        #$f = $this->_getReqFields($stage, $providerInfo);

        $params = [];
        $params['oauth_consumer_key'] = $consumerConf['consumer_key'];
        $params['oauth_nonce'] = BUtil::randomString(16);
        $params['oauth_timestamp'] = time();
        $params['oauth_signature_method'] = 'HMAC-SHA1';
        $params['oauth_version'] = !empty($providerInfo['version']) ? $providerInfo['version'] : '1.0';

        switch ($stage) {
            case 'request':
                $params['oauth_callback'] = BApp::href('oauth/callback');
                break;
            case 'access':
                $params['oauth_token'] = $consumerSess['request_token'];
                $params['oauth_verifier'] = $consumerSess['auth_verifier'];
                break;
        }

        $signatureBaseArr = [$this->_oauthEscape($method), $this->_oauthEscape($url)];
        ksort($params);
        $authHeaderArr = [];
        foreach ($params as $k => $v) {
            if ($k === 'oauth_signature') {
                continue;
            }
            if (is_array($v)) {
                sort($v);
                foreach ($v as $v1) {
                    $signatureBaseArr[] = $this->_oauthEscape($k . '=' . $v1);
                    $authHeaderArr[] = $k . '="' . $this->_oauthEscape($v1) . '"';
                }
            } else {
                $signatureBaseArr[] = $this->_oauthEscape($k . '=' . $v);
                $authHeaderArr[] = $k . '="' . $this->_oauthEscape($v) . '"';
            }
        }
        $signatureBase = join('&', $signatureBaseArr);
        $signatureKeyArr = [
            $this->_oauthEscape($consumerConf['consumer_secret']),
            !empty($consumerSess['request_token_secret']) ? $this->_oauthEscape($consumerSess['request_token_secret']) : '',
        ];
        $signatureKey = join('&', $signatureKeyArr);
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBase, $signatureKey, true));
        $headers['authorization'] = 'Authorization: OAuth ' . join(', ', $authHeaderArr);
        $response = BUtil::remoteHttp($method, $url, $params, $headers);
var_dump($response); exit;
        if (!$response) {
            throw new BException('OAuth getRequestToken HTTP error'); //TODO: more info
        }

        return $response;
    }

    public function getRequestToken()
    {
        $response = $this->_callV1('request');
        parse_str($response, $result);
        if (empty($result['oauth_callback_confirmed']) || empty($result['oauth_token']) || empty($result['oauth_secret'])) {
            throw new BException('OAuth getRequestToken error: '.print_r($result, 1));
        }

        $consumerSess['request_token'] = $result['oauth_token'];
        $consumerSess['request_token_secret'] = $result['oauth_secret'];
        return $result['oauth_token'];
    }

    public function getAuthUrl()
    {
        $providerName = $this->getProvider();
        $consumerSess =& $this->getConsumerSession($providerName);
        $providerInfo = $this->getProviderInfo($providerName);
        $params = [];
        $params['oauth_token'] = $consumerSess['request_token'];
        $authUrl = $providerInfo['auth'] . '?' . http_build_query($params);
        return $authUrl;
    }

    public function getAuthToken()
    {
        $providerName = $this->getProvider();
        $consumerSess =& $this->getConsumerSession($providerName);
        $consumerSess['auth_verifier'] = BRequest::i()->get('oauth_verifier');
        $authToken = BRequest::i()->get('oauth_token');
        if ($authToken !== $consumerSess['request_token']) {
            throw new BException('Invalid auth token: ' . $authToken);
        }
        return $authToken;
    }

    public function getAccessToken()
    {
        $response = $this->_callV1('access');
        parse_str($response, $result);
        if (empty($result['oauth_token']) || empty($result['oauth_secret'])) {
            throw new BException('OAuth getRequestToken error: '.print_r($result, 1));
        }

        $token = $result['oauth_token'];
        $secret = $result['oauth_secret'];
        unset($result['oauth_token'], $result['oauth_secret']);

        $modelData = ['provider' => $providerName, 'token' => $token];
        $tokenModel = FCom_OAuth_Model_ConsumerToken::i()->loadOrCreate($modelData);
        $tokenModel->set('token_secret', $secret)->setData($result)->save();

        $this->onAfterGetAccessToken($tokenModel);

        BEvents::i()->fire(__METHOD__ . ':after', [ 'token_model' => $tokenModel ]);

        return $tokenModel;
    }

    public function onAfterGetAccessToken($tokenModel)
    {
        // better to have everything in the same module, than two way module references
        if (BModuleRegistry::i()->isLoaded('FCom_Admin')) {
            $userId = $tokenModel->get('admin_id');
            $hlp = FCom_Customer_Model_Customer::i();
            if ($userId && !$hlp->isLoggedIn()) {
                $user = $hlp->load($userId)->login();
            }
        }

        if (BModuleRegistry::i()->isLoaded('FCom_Customer')) {
            $userId = $tokenModel->get('customer_id');
            $hlp = FCom_Customer_Model_Customer::i();
            if ($userId && !$hlp->isLoggedIn()) {
                $user = $hlp->load($userId)->login();
            }
        }
    }

    protected function _oauthEscape($string)
    {
        return str_replace(
            ['%7E', '+',   '!',   '*',   '\'',  '(',   ')'  ],
            ['~',   '%20', '%21', '%2A', '%27', '%28', '%29'],
            rawurlencode($string)
        );
    }
}
