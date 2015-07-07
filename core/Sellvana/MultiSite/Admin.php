<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 * @property Sellvana_MultiSite_Model_SiteUser $Sellvana_MultiSite_Model_SiteUser
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

    public function onUsersFormPostAfter($args)
    {
        $uId = $args['model']->id();
        $new = $this->BRequest->post('multisite');
        foreach ($new as $sId => $roles) {
            $new[$sId] = array_flip($roles);
        }
        $hlp = $this->Sellvana_MultiSite_Model_SiteUser;
        $exists = $hlp->orm()->where('user_id', $uId)->find_many_assoc(['site_id', 'role_id']);
        if ($exists) {
            foreach ($exists as $sId => $roles) {
                foreach ($roles as $rId => $r) {
                    if (empty($new[$sId][$rId])) {
                        $r->delete();
                        unset($exists[$sId][$rId]);
                    }
                }
            }
        }
        foreach ($new as $sId => $roles) {
            foreach ($roles as $rId => $_) {
                if (empty($exists[$sId][$rId])) {
                    $hlp->create(['user_id' => $uId, 'site_id' => $sId, 'role_id' => $rId])->save();
                }
            }
        }
    }
}
