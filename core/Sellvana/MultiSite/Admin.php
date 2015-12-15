<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Admin
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 * @property Sellvana_MultiSite_Model_SiteUser $Sellvana_MultiSite_Model_SiteUser
 * @property Sellvana_CatalogFields_Model_ProductFieldData $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_CatalogFields_Model_Field $Sellvana_CatalogFields_Model_Field
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

    public function onGetProductFieldSetData($args)
    {
        foreach ($args['data'] as $productId => $productData) {
            $args['data'][$productId]['site_values'] = [];
            foreach ($productData as $fieldSetId => $fieldSetData) {
                foreach ($fieldSetData['fields'] as $fieldId => $fieldData) {
                    $data = json_decode($fieldData['serialized']);
                    $siteId = $data->site_id ?: 'default';
                    if (empty($args['data']['site_values'][$siteId])) {
                        $args['data']['site_values'][$siteId] = [];
                    }
                    if (isset($args['data'][$productId]['site_values'][$siteId][$fieldData['id']])) {
                        $args['data'][$productId]['site_values'][$siteId][$fieldData['id']] .= ',' . $fieldData['value'];
                    } else {
                        $args['data'][$productId]['site_values'][$siteId][$fieldData['id']] = $fieldData['value'];
                    }
                }
            }
        }
    }

    public function onFindManyBefore($args)
    {
        /** @var BORM $orm */
        $orm = $args['orm'];
        $alias = $orm->table_alias();
        $orm->order_by_asc($alias . '.site_id');
    }

    public function onProductFormPostBefore($args)
    {
        /** @var Sellvana_Catalog_Model_Product $product */
        $siteData = $this->BUtil->fromJson($this->BRequest->post('site_values'));
        $product = &$args['model'];
        if (!$siteData || !$product->id()) {
            return;
        }

        $product->set('multisite_fields', $siteData);
    }

}
