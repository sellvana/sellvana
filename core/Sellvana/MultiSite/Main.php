<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Main
 *
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 */
class Sellvana_MultiSite_Main extends BClass
{
    public function onBeforeBootstrap()
    {
        if ($this->BConfig->get('install_status') === 'installed') {
            BDb::connect();
            $siteData = $this->Sellvana_MultiSite_Model_Site->findByDomain();
            if (!$siteData) {
                return;
            }
            $this->BApp->set('current_site', $siteData);
        }
        //TODO: implement relevant updates to the environment based on the current site data
    }

    public function getCurrentSiteData()
    {
        return $this->BApp->get('current_site');
    }
}
