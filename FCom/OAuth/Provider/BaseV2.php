<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_OAuth_Provider_BaseV2 extends FCom_OAuth_Provider_Abstract
{
    public function loginAction()
    {
        return $this->getAuthUrl();
    }

    public function callbackAction()
    {
        $this->getAuthToken();
        $this->getAccessToken();
        return $this;
    }

    public function getAuthUrl()
    {
        $hlp = FCom_OAuth_Main::i();
        $providerName = $hlp->getProvider();
        $consumerConf = BConfig::i()->get('modules/FCom_OAuth/' . $providerName);
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $providerInfo = $hlp->getProviderInfo($providerName);
        $params = [];
        $params['response_type'] = 'code';
        $params['client_id'] = $consumerConf['consumer_key'];
        $params['redirect_uri'] = BApp::href('oauth/callback');
        $params['state'] = $consumerSess['state'] = BUtil::randomString(16);
        $params['scope'] = !empty($providerInfo['scope']) ? $providerInfo['scope'] : '';
        $authUrl = $providerInfo['auth'] . '?' . http_build_query($params);
        return $authUrl;
    }

    public function getAuthToken()
    {
        if (BRequest::i()->get('error')) {
            throw new BException(BRequest::i()->get('error_description'));
        }
        $hlp = FCom_OAuth_Main::i();
        $providerName = $hlp->getProvider();
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $consumerSess['code'] = BRequest::i()->get('code');
        $state = BRequest::i()->get('state');
        if ($state !== $consumerSess['state']) {
            throw new BException('Invalid state: ' . $state);
        }
        return $consumerSess['code'];
    }

    public function getAccessToken()
    {
        $hlp = FCom_OAuth_Main::i();
        $providerName = $hlp->getProvider();
        $consumerConf = BConfig::i()->get('modules/FCom_OAuth/' . $providerName);
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $providerInfo = $hlp->getProviderInfo($providerName);
        $params = [];
        $params['client_id'] = $consumerConf['consumer_key'];
        $params['client_secret'] = $consumerConf['consumer_secret'];
        $params['code'] = $consumerSess['code'];
        unset($consumerSess['code']);
        $params['grant_type'] = 'authorization_code';
        $params['redirect_uri'] = BApp::href('oauth/callback');
        $response = BUtil::remoteHttp('POST', $providerInfo['access'], $params, ['curl' => 1]);
        if (!$response) {
            throw new BException('Error during access_token HTTP request');
        }
        if ($response[0] === '{') {
            $result = BUtil::fromJson($response);
        } else {
            parse_str($response, $result);
        }
        if (!empty($result['error'])) {
            throw new BException($result['error_description']);
        }
        if (empty($result['access_token'])) {
            echo "<pre>"; var_dump($result); exit;
        }
        $token = $result['access_token'];
        unset($result['access_token']);

        $modelData = ['provider' => $providerName, 'token' => $token];
        $tokenModel = FCom_OAuth_Model_ConsumerToken::i()->loadOrCreate($modelData);
        $tokenModel->setData($result)->save();

        BEvents::i()->fire(__METHOD__ . ':after', [ 'token_model' => $tokenModel ]);

        return $tokenModel;
    }
}
