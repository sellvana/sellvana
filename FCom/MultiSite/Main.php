<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_MultiSite_Main
 *
 * @property FCom_MultiSite_Model_Site $FCom_MultiSite_Model_Site
 */
class FCom_MultiSite_Main extends BClass
{
    public function beforeBootstrap()
    {
        if ($this->BConfig->get('install_status') === 'installed') {
            BDb::connect();
            $siteData = $this->FCom_MultiSite_Model_Site->findByDomain();
            if (!$siteData) {
                return;
            }
        }
        //TODO: implement relevant updates to the environment based on the current site data
    }
}
