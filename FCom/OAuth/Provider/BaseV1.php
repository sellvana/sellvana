<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_OAuth_Provider_BaseV1
 *
 * @property FCom_OAuth_Main $FCom_OAuth_Main
 * @property FCom_OAuth_Model_ConsumerToken $FCom_OAuth_Model_ConsumerToken
 */

class FCom_OAuth_Provider_BaseV1 extends FCom_OAuth_Provider_Abstract
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

    public function getRequestToken()
    {
        $response = $this->_signAndCall('request');
        parse_str($response, $result);
        if (empty($result['oauth_callback_confirmed']) || empty($result['oauth_token']) || empty($result['oauth_token_secret'])) {
            throw new BException('OAuth getRequestToken error: '.print_r($result, 1));
        }

        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $consumerSess['request_token'] = $result['oauth_token'];
        $consumerSess['request_token_secret'] = $result['oauth_token_secret'];
        return $result['oauth_token'];
    }

    public function getAuthUrl()
    {
        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $providerInfo = $hlp->getProviderInfo($providerName);
        $params = [];
        $params['oauth_token'] = $consumerSess['request_token'];
        $authUrl = $providerInfo['auth'] . '?' . http_build_query($params);
        return $authUrl;
    }

    public function getAuthToken()
    {
        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $consumerSess['auth_verifier'] = $this->BRequest->get('oauth_verifier');
        $authToken = $this->BRequest->get('oauth_token');
        if (empty($consumerSess['request_token']) || $authToken !== $consumerSess['request_token']) {
            throw new BException('Invalid auth token: ' . $authToken);
        }
        return $authToken;
    }

    public function getAccessToken()
    {
        $response = $this->_signAndCall('access');
        parse_str($response, $result);
        if (empty($result['oauth_token']) || empty($result['oauth_token_secret'])) {
            throw new BException('OAuth getRequestToken error: '.print_r($result, 1));
        }

        $token = $result['oauth_token'];
        $secret = $result['oauth_token_secret'];
        unset($result['oauth_token'], $result['oauth_token_secret']);

        $modelData = ['provider' => $providerName, 'token' => $token];
        $tokenModel = $this->FCom_OAuth_Model_ConsumerToken->loadOrCreate($modelData);
        $tokenModel->set('token_secret', $secret)->setData($result)->save();

        $this->onAfterGetAccessToken($tokenModel);

        $this->BEvents->fire(__METHOD__ . ':after', [ 'token_model' => $tokenModel ]);

        return $tokenModel;
    }

    protected function _signAndCall($stage)
    {
        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        $providerInfo = $hlp->getProviderInfo($providerName);
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $consumerConf = $this->BConfig->get('modules/FCom_OAuth/' . $providerName);
        if (empty($consumerConf['consumer_key']) || empty($consumerConf['consumer_secret'])) {
            throw new BException('Missing consumer key or secret for ' . $providerName);
        }

        $reqInfo = $providerInfo[$stage];
        if (!is_array($reqInfo)) {
            $reqInfo = ['url' => $reqInfo];
        }
        $url = $reqInfo['url'];
        $method = !empty($reqInfo['method']) ? $reqInfo['method'] : 'GET';
        #$f = $this->_getReqFields($stage, $providerInfo);

        $params = [];
        $params['oauth_consumer_key'] = $consumerConf['consumer_key'];
        $params['oauth_nonce'] = $this->BUtil->randomString(32);
        $params['oauth_timestamp'] = time();
        $params['oauth_signature_method'] = 'HMAC-SHA1';
        $params['oauth_version'] = !empty($providerInfo['version']) ? $providerInfo['version'] : '1.0';

        switch ($stage) {
            case 'request':
                $params['oauth_callback'] = $this->BApp->href('oauth/callback');
                break;
            case 'access':
                $params['oauth_token'] = $consumerSess['request_token'];
                $params['oauth_verifier'] = $consumerSess['auth_verifier'];
                break;
        }

        $signatureBaseArr = [];
        ksort($params);
        foreach ($params as $k => $v) {
            if ($k === 'oauth_signature') {
                continue;
            }
            if (is_array($v)) {
                sort($v);
                foreach ($v as $v1) {
                    $signatureBaseArr[] = $k . '=' . $this->_oauthEscape($v1);
                }
            } else {
                $signatureBaseArr[] = $k . '=' . $this->_oauthEscape($v);
            }
        }
        $signatureBase = $this->_oauthEscape($method) . '&' . $this->_oauthEscape($url) . '&'
            . $this->_oauthEscape(join('&', $signatureBaseArr));
        $signatureKeyArr = [
            $this->_oauthEscape($consumerConf['consumer_secret']),
            !empty($consumerSess['request_token_secret']) ? $this->_oauthEscape($consumerSess['request_token_secret']) : '',
        ];
        $signatureKey = join('&', $signatureKeyArr);
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBase, $signatureKey, true));

        $authHeaderArr = [];
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                sort($v);
                foreach ($v as $v1) {
                    $authHeaderArr[] = $k . '="' . $this->_oauthEscape($v1) . '"';
                }
            } else {
                $authHeaderArr[] = $k . '="' . $this->_oauthEscape($v) . '"';
            }
        }
        $postMethod = !empty($providerInfo['post_method']) ? $providerInfo['post_method'] : 'header';
        $postParams = [];
        $headers = [];
        switch ($postMethod) {
            case 'url':
                $url .= '?' . http_build_query($params);
                break;
            case 'header':
                $headers[] = 'Authorization: OAuth ' . join(',', $authHeaderArr);
                break;
            case 'body':
                $postParams = $params;
                break;
        }
        $response = $this->BUtil->remoteHttp($method, $url, $postParams, $headers, ['curl' => 1]);
        if (!$response) {
            throw new BException('OAuth getRequestToken HTTP error'); //TODO: more info
        }

        return $response;
    }

    protected function _oauthEscape($string)
    {
        return str_replace(['%7E', '+'], ['~', ' '], rawurlencode($string));
        /*
        return str_replace(
            ['%7E', '+',   '!',   '*',   '\'',  '(',   ')'  ],
            ['~',   '%20', '%21', '%2A', '%27', '%28', '%29'],
            rawurlencode($string)
        );
        */
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

}
