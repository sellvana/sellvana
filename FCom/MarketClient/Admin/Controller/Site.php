<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_MarketClient_Admin_Controller_Site
 *
 * @property FCom_MarketClient_RemoteApi $FCom_MarketClient_RemoteApi
 */
class FCom_MarketClient_Admin_Controller_Site extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'market_client';

    public function action_connect()
    {
        $data = $this->FCom_MarketClient_RemoteApi->setupConnection();
        if (empty($data['url'])) {
            $data['url'] = 'modules';
            $this->message('Could not connect to Marketplace', 'error');
        }
        $this->BResponse->redirect($data['url']);
    }

    public function action_check_updates__POST()
    {
        try {
            $redirectUrl = $this->BRequest->referrer();
            $result = $this->FCom_MarketClient_RemoteApi->getModulesVersions(true, true);
            foreach ($result as $modName => $modUpgrade) {
                if (!empty($modUpgrade['can_update'])) {
                    $upgradeModNames[] = $modName;
                }
            }
            if (!empty($upgradeModNames)) {
                $this->message('Upgrades found: ' . join(', ', $upgradeModNames));
                if ($this->BRequest->get('install')) {
                    $redirectUrl = 'marketclient/module/install?mod_name=' . join(',', $upgradeModNames);
                }
            } else {
                $this->message('No upgrades were found');
            }
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
        }
        $this->BResponse->redirect($redirectUrl);
    }
}
