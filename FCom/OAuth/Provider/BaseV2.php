<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_OAuth_Provider_BaseV2
 *
 * @property FCom_OAuth_Main $FCom_OAuth_Main
 * @property FCom_OAuth_Model_ConsumerToken $FCom_OAuth_Model_ConsumerToken
 */

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
        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        $consumerConf = $this->BConfig->get('modules/FCom_OAuth/' . $providerName);
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $providerInfo = $hlp->getProviderInfo($providerName);
        $params = [];
        $params['response_type'] = 'code';
        $params['client_id'] = $consumerConf['consumer_key'];
        $params['redirect_uri'] = $this->BApp->href('oauth/callback');
        $params['state'] = $consumerSess['state'] = $this->BUtil->randomString(16);
        $params['scope'] = !empty($providerInfo['scope']) ? $providerInfo['scope'] : '';
        $authUrl = $providerInfo['auth'] . '?' . http_build_query($params);
        return $authUrl;
    }

    public function getAuthToken()
    {
        if ($this->BRequest->get('error')) {
            throw new BException($this->BRequest->get('error_description'));
        }
        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $consumerSess['code'] = $this->BRequest->get('code');
        $state = $this->BRequest->get('state');
        if ($state !== $consumerSess['state']) {
            throw new BException('Invalid state: ' . $state);
        }
        return $consumerSess['code'];
    }

    public function getAccessToken()
    {
        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        $consumerConf = $this->BConfig->get('modules/FCom_OAuth/' . $providerName);
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $providerInfo = $hlp->getProviderInfo($providerName);
        $params = [];
        $params['client_id'] = $consumerConf['consumer_key'];
        $params['client_secret'] = $consumerConf['consumer_secret'];
        $params['code'] = $consumerSess['code'];
        unset($consumerSess['code']);
        $params['grant_type'] = 'authorization_code';
        $params['redirect_uri'] = $this->BApp->href('oauth/callback');
        $response = $this->BUtil->remoteHttp('POST', $providerInfo['access'], $params, ['curl' => 1]);
        if (!$response) {
            throw new BException('Error during access_token HTTP request');
        }
        if ($response[0] === '{') {
            $result = $this->BUtil->fromJson($response);
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
        $tokenModel = $this->FCom_OAuth_Model_ConsumerToken->loadOrCreate($modelData);
        $tokenModel->setData($result)->save();

        $this->BEvents->fire(__METHOD__ . ':after', [ 'token_model' => $tokenModel ]);

        return $tokenModel;
    }
}
