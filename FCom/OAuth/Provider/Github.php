<?php

class FCom_OAuth_Provider_Github extends FCom_OAuth_Provider_Base
{
    public function getRequestToken()
    {
        return '';
    }

    public function getAuthUrl()
    {
        $hlp = FCom_OAuth_Main::i();
        $providerName = $hlp->getProvider();
        $consumerConf = BConfig::i()->get('modules/FCom_OAuth/' . $providerName);
        $consumerSess =& $hlp->getConsumerSession($providerName);
        $providerInfo = $hlp->getProviderInfo($providerName);
        $params = [];
        $params['client_id'] = $consumerConf['consumer_key'];
        $params['redirect_uri'] = $hlp->getReturnUrl();
        $params['state'] = $consumerSess['state'] = BUtil::randomString(16);
        $params['scope'] = 'user,user:email';
        $authUrl = $providerInfo['auth'] . '?' . http_build_query($params);
        return $authUrl;
    }

    public function getAuthToken()
    {
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
        $response = BUtil::remoteHttp('POST', $providerInfo['access'], $params);
        if (!$response) {

        }
        parse_str($response, $result);
        if (!empty($result['error'])) {
            throw new BException($result['error_description']);
        }

        $token = $result['access_token'];

        $modelData = ['provider' => $providerName, 'token' => $token];
        $tokenModel = FCom_OAuth_Model_ConsumerToken::i()->loadOrCreate($modelData);
        $tokenModel->setData($result)->save();

        BEvents::i()->fire(__METHOD__ . ':after', [ 'token_model' => $tokenModel ]);

        return $tokenModel;
    }
}
