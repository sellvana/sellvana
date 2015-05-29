<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 */
class Sellvana_MultiSite_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'multi_site' => BLocale::i()->_('Multi Site'),
            'settings/Sellvana_MultiSite' => BLocale::i()->_('Multi Site Settings'),
        ]);
    }

    public function onSettingsIndexGet($args)
    {
        $siteId = $this->BRequest->get('site');
        if ($siteId) {
            $site = $this->Sellvana_MultiSite_Model_Site->load($siteId);
            if ($site) {
                $args['model'] = clone $this->BConfig;
                $config = $site->getData('config');
                if ($config) {
                    $args['model']->add($config);
                }
            } else {
                throw new BException('Invalid site ID');
            }
        }
    }

    public function onSettingsIndexPost($args)
    {
        $siteId = $this->BRequest->get('site');
        if ($siteId) {
            $site = $this->Sellvana_MultiSite_Model_Site->load($siteId);
            if ($site) {
                $config = $site->getData('config');
                $data = $args['post']['config'];
                unset($data['X-CSRF-TOKEN']);
                $diff = $this->BUtil->arrayDiffRecursive($data, $this->BConfig->get());
                $config = $this->BUtil->arrayMerge($config, $diff);
                $site->setData('config', $config)->save();
                $args['skip_default_handler'] = true;
            } else {
                throw new BException('Invalid site ID');
            }
        }
    }
}
