<?php

class FCom_MarketClient_Admin_Controller_Site extends FCom_Admin_Controller_Abstract
{
    public function action_request_nonce()
    {
        $hlp = FCom_MarketClient_RemoteApi::i();
        $data = $hlp->requestSiteNonce();
        $response = array(
            'nonce' => !empty($data['nonce']) ? $data['nonce'] : null,
            'login_required' => !empty($data['login_required']) ? $data['login_required'] : null,
            'setup_url' => $hlp->getUrl('market/site/setup', array(
                'nonce' => $data['nonce'],
                'target' => $hlp->getUrl(),
                'auto_login' => BConfig::i()->get('modules/FCom_MarketClient/auto_login'),
            )),
        );
        BResponse::i()->json($response);
    }

    public function action_request_key()
    {
        $hlp = FCom_MarketClient_RemoteApi::i();
        $nonce = BRequest::i()->get('nonce');
        $response = $hlp->requestSiteKey($nonce);
        //TODO: handle error statuses
        if (!empty($response['site_key'])) {
            BConfig::i()->set('modules/FCom_MarketClient/site_key', $response['site_key'], false, true);
            FCom_Core_Main::i()->writeConfigFiles('local');
        }
        BResponse::i()->redirect($hlp->getUrl());
    }
}
