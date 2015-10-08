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
        $orm->order_by_asc('pf.site_id');
    }

    public function saveProductsFieldSiteData($products)
    {
        $siteValues = json_decode($this->BRequest->post('site_values'), true);
        if (!$siteValues) {
            return $this;
        }
        unset($siteValues->default);

        $fields = $this->Sellvana_CatalogFields_Model_Field->getAllFields('id');

        $pIds = $this->BUtil->arrayToOptions($products, '.id');
        if (!$pIds) {
            return $this;
        }
        /** @var Sellvana_CatalogFields_Model_ProductFieldData[][][][] $fieldsData */
        $rawFieldsData = $this->Sellvana_CatalogFields_Model_ProductFieldData->orm('pf')
            ->where_in('product_id', $pIds)
            ->find_many();
        $fieldsData = [];
        foreach ($rawFieldsData as $rawData) {
            $siteId = $rawData->get('site_id') ?: 'default';
            if (empty($fieldsData[$rawData->get('product_id')])) {
                $fieldsData[$rawData->get('product_id')] = [];
            }

            if (empty($fieldsData[$rawData->get('product_id')][$siteId])) {
                $fieldsData[$rawData->get('product_id')][$siteId] = [];
            }

            if (empty($fieldsData[$rawData->get('product_id')][$siteId][$rawData->get('field_id')])) {
                $fieldsData[$rawData->get('product_id')][$siteId][$rawData->get('field_id')] = [];
            }

            array_push($fieldsData[$rawData->get('product_id')][$siteId][$rawData->get('field_id')], $rawData);
        }

        $options = $this->Sellvana_CatalogFields_Model_FieldOption->preloadAllFieldsOptions()->getAllFieldsOptions();
        $optionsByLabel = [];
        foreach ($options as $fieldId => $fieldOptions) {
            foreach ($fieldOptions as $optionId => $option) {
                $optionsByLabel[$fieldId][strtolower($option->get('label'))] = $option->id();
            }
        }
        foreach ($products as $product) { // go over products
#echo "<pre>"; debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); echo "</pre>"; var_dump($product->as_array());
            $pId = $product->id();
            $pData = $product->as_array();
            foreach ($siteValues as $siteId => $siteValue) {
                foreach ($siteValue as $fieldId => $value) { // go over all product fields data
                    if (empty($fields[$fieldId])) {
                        continue;
                    }

                    $field = $fields[$fieldId];
                    $fId = $field->id();
                    $fieldType = $field->get('table_field_type');
                    $fieldCode = $field->get('field_code');
                    $tableColumn = $this->Sellvana_CatalogFields_Model_ProductFieldData->getTableColumn($fieldType);

                    if ($fieldType === 'options') {
                        $value = explode(',', $value);
                    } elseif (!is_array($value)) {
                        $value = [$value];
                    }

                    foreach ($value as $singleValue) {
                        if (true) { // if this product has this field data
                        //if (null !== $product->get($fieldCode)) { // if this product has this field data
                            if (!empty($fieldsData[$pId][$siteId][$fId])) { // if this field data record already exists
                                $fData = array_shift($fieldsData[$pId][$siteId][$fId]);
                                if (!empty($pData['_custom_fields_remove']) && in_array($fId, $pData['_custom_fields_remove'])) {
                                    $fData->delete();
                                    $product->set($fieldCode, null);
                                    continue;
                                }
                            } else { // if this is a new entry
                                $fData = $this->Sellvana_CatalogFields_Model_ProductFieldData->create([
                                    'product_id' => $pId,
                                    'field_id' => $fId,
                                    'site_id' => $siteId,
                                    'set_id' => (!empty($fieldsData[$pId]['default'][$fId][0])) ? $fieldsData[$pId]['default'][$fId][0]->get('set_id') : null
                                ]);
                            }
                            if ($fieldType === 'options') {
                                $valueLower = strtolower($singleValue);
                                if (!empty($optionsByLabel[$fId][$valueLower])) { // option exists?
                                    $singleValue = $optionsByLabel[$fId][$valueLower];
                                } else {                                   // option doesn't exist
                                    if ($this->Sellvana_CatalogFields_Model_ProductFieldData->getAutoCreateOptions()) { // allow option auto-creation?
                                        $optionId = $this->Sellvana_CatalogFields_Model_FieldOption->create([
                                            'field_id' => $fId,
                                            'label' => $singleValue,
                                        ])->save()->id();
                                        $singleValue = $optionId;
                                        $optionsByLabel[$fId][$valueLower] = $optionId;
                                    } else { // don't auto-create
                                        $singleValue = null;
                                    }
                                }
                            }
                            $fData->set($tableColumn, $singleValue);
                            $fData->save();
                        } else { // this product doesn't have data for this field
                            if (!empty($fieldsData[$pId][$siteId][$fId])) { // there's old data
                                foreach ($fieldsData[$pId][$siteId][$fId] as $wrongData) {
                                    $wrongData->set($tableColumn, null); // delete old data record for this product/field
                                }
                            }
                        }
                    }
                }
            }

            // cleaning up deleted values
            foreach ($fieldsData as $prodData) {
                foreach ($prodData as $siteId => $siteData) {
                    if ($siteId == 'default') {
                        continue;
                    }
                    foreach ($siteData as $fieldData) {
                        foreach ($fieldData as $valueData) {
                            $valueData->delete();
                        }
                    }
                }
            }
        }

        return $this->Sellvana_CatalogFields_Model_ProductFieldData;
    }
}
