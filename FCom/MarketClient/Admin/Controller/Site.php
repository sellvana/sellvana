<?php

class FCom_MarketClient_Admin_Controller_Site extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client';

    public function action_connect()
    {
        $data = FCom_MarketClient_RemoteApi::i()->setupConnection();
        if (empty($data['url'])) {
            $data['url'] = 'modules';
            $this->message('Could not connect to Marketplace', 'error');
        }
        BResponse::i()->redirect($data['url']);
    }

    public function action_check_updates__POST()
    {
        try {
            $redirectUrl = BRequest::i()->referrer();
            $result = FCom_MarketClient_RemoteApi::i()->getModulesVersions(true, true);
            foreach ($result as $modName => $modUpgrade) {
                if (!empty($modUpgrade['can_update'])) {
                    $upgradeModNames[] = $modName;
                }
            }
            if (!empty($upgradeModNames)) {
                $this->message('Upgrades found: ' . join(', ', $upgradeModNames));
                if (BRequest::i()->get('install')) {
                    $redirectUrl = 'marketclient/module/install?mod_name=' . join(',', $upgradeModNames);
                }
            } else {
                $this->message('No upgrades were found');
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
        }
        BResponse::i()->redirect($redirectUrl);
    }
}
