<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Frontend
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 */
class Sellvana_MultiSite_Frontend extends BClass
{
    static protected $_currentSite;

    public function onBeforeBootstrap()
    {
        if ($this->BConfig->get('install_status') === 'installed') {
            BDb::connect();
            $hlp = $this->Sellvana_MultiSite_Model_Site;
            $siteId = $hlp->findIdByDomain();
            if ($siteId) {
                $site = $hlp->load($siteId);
                if ($site) {
                    static::$_currentSite = $site;
                    $config = $site->getData('config');
                    if ($config) {
                        $this->BConfig->add($config);
                    }
                } else {
                    $hlp->createDomainMap();
                }
            }
        }
        //TODO: implement relevant updates to the environment based on the current site data
    }

    public function getCurrentSite()
    {
        return static::$_currentSite;
    }

    public function onBaseLayout()
    {
        if (static::$_currentSite) {
            $layoutData = static::$_currentSite->getData('layout');
            if ($layoutData) {
                $context = ['type' => 'site', 'main_view' => ''];
                $layoutUpdate = $this->FCom_Core_LayoutEditor->compileLayout($layoutData, $context);
                if ($layoutUpdate) {
                    $this->BLayout->addLayout('site_base', $layoutUpdate)->applyLayout('site_base');
                }
            }
        }
    }
}
