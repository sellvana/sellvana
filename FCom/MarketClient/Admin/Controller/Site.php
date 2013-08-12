<?php

class FCom_MarketClient_Admin_Controller_Site extends FCom_Admin_Controller_Abstract
{
    public function action_request_nonce()
    {
        $hlp = FCom_MarketClient_RemoteApi::i();
        $data = $hlp->requestSiteNonce();
        $response = array(
            'nonce' => $data['nonce'],
            'url' => $hlp->getUrl('market/site/setup', array(
                'nonce' => $data['nonce'],
                'target' => $hlp->getUrl(),
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
            FCom_Core_Main::i()->writeLocalConfig();
        }
        BResponse::i()->redirect($hlp->getUrl());
    }
}
