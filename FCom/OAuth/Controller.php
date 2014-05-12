<?php

class FCom_OAuth_Controller extends FCom_Core_Controller_Abstract
{
    public function action_login()
    {
        $hlp = FCom_OAuth_Main::i();
        $returnUrl = BRequest::i()->get('redirect_to');
        $providerName = BRequest::i()->param('provider', true);
        if ($returnUrl) {
            $hlp->setReturnUrl($returnUrl);
        }
        try {
            $hlp->setProvider($providerName);
            $authUrl = $hlp->loginAction();
            BResponse::i()->redirect($authUrl);
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error');
            BResponse::i()->redirect($returnUrl);
        }
    }

    public function action_callback()
    {
        $hlp = FCom_OAuth_Main::i();
        $returnUrl = $hlp->getReturnUrl();
        try {
            $hlp->callbackAction();
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error');
        }
        BResponse::i()->redirect($returnUrl);
    }

    public function action_test()
    {
        $prov = FCom_OAuth_Main::i()->setProvider('twitter')->getProviderInfo('twitter');
        $oauth = new OAuthSimple();
        $c = BConfig::i()->get('modules/FCom_OAuth/twitter');
        $signatures = [ 'consumer_key' => $c['consumer_key'], 'shared_secret' => $c['consumer_secret']];
        $oauth->setAction('POST');
        $signed = $oauth->sign(['path' => $prov['request'], 'signatures' => $signatures]);
echo "<pre>"; var_dump($signed);
        //$response = BUtil::remoteHTTP('GET', $prov['request_url'], $signed['parameters'], ['Authorization: ' . $signed['header']]);
        $response = BUtil::remoteHTTP('POST', $signed['signed_url'], [], ['Authorization: ' . $signed['header']]);
echo "<pre>"; var_dump($response);
exit;
    }

}
