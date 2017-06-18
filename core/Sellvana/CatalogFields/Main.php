<?php

/**
 * Class Sellvana_CatalogFields_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property FCom_Core_Model_Field $FCom_Core_Model_Field
 * @property Sellvana_CatalogFields_Model_ProductField $Sellvana_CatalogFields_Model_ProductField
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property FCom_Core_Model_FieldOption $FCom_Core_Model_FieldOption
 * @property Sellvana_CatalogFields_Model_ProductFieldData $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_MultiSite_Main $Sellvana_MultiSite_Main
 */
class Sellvana_CatalogFields_Main extends BClass
{
    protected $_types;
    protected $_disabled;

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'catalog_fields' => 'Catalog Custom Fields'
        ]);

        $this->FCom_Core_Model_Field->registerFieldType('product', [
            'label' => 'Products',
            'class' => 'Sellvana_CatalogFields_Model_ProductField',
        ]);
    }

    /**
     * @param $flag
     * @return $this
     */
    public function disable($flag = true)
    {
        $this->_disabled = $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return (bool)$this->_disabled;
    }

    /**
     * @param array $args
     */
    public function onProductVariantFindAfter($args)
    {
        if ($this->_disabled) {
            return;
        }
        $m = $args['result'];
        if(!$m){
            return;
        }
        if(!is_array($m)){
            $m = [$m];
        }

        foreach ($m as $model) {
            /** @var Sellvana_CatalogFields_Model_ProductVariant $model */
            $model->onAfterLoad();
        }
    }

    /**
     * Process Save/Remove product custom fields
     * @param  Sellvana_Catalog_Model_Product $p
     */
    protected function _processProductCustom(Sellvana_Catalog_Model_Product $p)
    {
        $fieldSets = $p->get('custom_fields');

        if (is_array($fieldSets)) {
            foreach ($fieldSets as $set) {
                if (empty($set['fields'])) {
                    continue;
                }
                foreach ($set['fields'] as $field) {
                    $p->set($field['field_code'], $this->BUtil->dataGet($field, 'value'));
                    $fieldModel = $this->FCom_Core_Model_Field->load($field['id']);
                    if (!$fieldModel || in_array($field['admin_input_type'], ['select', 'multiselect'])) {
                        continue;
                    }

                    $fieldModel->setData(
                        'frontend_label_translation', $this->BUtil->dataGet($field, 'lang_fields')
                    )->save();
                }
            }
        } else {
            $fieldSets = [];
        }

        $pfdHlp = $this->Sellvana_CatalogFields_Model_ProductFieldData;
        $pfdHlp->saveModelsFieldData([$p]);

        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiSite')) {
            $this->Sellvana_MultiSite_Main->saveProductsFieldSiteData([$p]);
        }

        $fieldsDataArr = $pfdHlp->fetchModelsFieldData([$p->id()]);
        if (!empty($fieldsDataArr[$p->id()])) {
            $fieldsData = $fieldsDataArr[$p->id()];
            foreach ($fieldSets as $set) {
                if (empty($set['fields'])) {
                    continue;
                }
                foreach ($set['fields'] as $field) {
                    /** @var Sellvana_CatalogFields_Model_ProductFieldData $row */
                    foreach ($fieldsData as $row) {
                        if ($row->get('field_id') != $field['id']) {
                            continue;
                        }
                        $row->set([
                            'set_id' => $set['id'] ?: null,
                            'position' => $this->BUtil->dataGet($field, 'position'),
                        ])->save();
                    }
                }
            }
        }
    }

    /**
     * @param $args
     * @throws BException
     */
    public function onProductAfterSave($args)
    {
        if ($this->BDebug->is(BDebug::MODE_IMPORT)) {
            return;
        }
        $this->_processProductCustom($args['model']);
    }

    /**
     * @param $args
     * @return mixed|string
     * @todo refactor to use layout
     */
    public function hookCustomFieldFilters($args)
    {
        $category = false;
        if (is_object($args['category'])) {
            $category = $args['category'];
        }

        $customFields = $this->FCom_Core_Model_Field->orm()
            ->where_in('facet_select', ['Inclusive', 'Exclusive'])
            ->where('frontend_show', 1)
            ->order_by_asc('sort_order')
            ->find_many();

        if (!$customFields) {
            return;
        }

        $filter = $this->BRequest->get('f');
        $currentFilter = [];
        $excludeFilters = [];
        if (!empty($filter)) {
            foreach ($filter as $fkey => $fval) {
                $fkey = urldecode($fkey);
                /** @var FCom_Core_Model_Field $field */
                $field = $this->FCom_Core_Model_Field->orm()->where('field_code', $fkey)->find_one();
                $currentFilter[$field->frontend_label][] = [
                    'key' => $field->field_code,
                    'facet_select' => $field->facet_select,
                    'value' => $fval,
                ];
                if (is_array($fval)) {
                    foreach ($fval as $fvalsingle) {
                        $excludeFilters[$field->frontend_label][] = $fvalsingle;
                    }
                } else {
                    $excludeFilters[$field->frontend_label][] = $fval;
                }
            }
        }


        $groups = [];
        foreach ($customFields as $cf) {
            if ($category) {
                $productOrm = $category->productsORM();
            } else {
                $productOrm = $this->Sellvana_Catalog_Model_Product->orm();
            }
            $products = $productOrm->where_not_equal($cf->field_code, '')->group_by($cf->field_code)->find_many();
            if (empty($products)) {
                continue;
            }
            $values = [];
            foreach ($products as $p) {
                if (isset($excludeFilters[$cf->frontend_label]) &&
                    in_array($p-> {$cf->field_code}, $excludeFilters[$cf->frontend_label])
                ) {
                    continue;
                }
                $values[] = $p-> {$cf->field_code};
            }
            if (empty($values)) {
                continue;
            }
            $groups[$cf->frontend_label] = [
                'key' => $cf->field_code,
                'facet_select' => $cf->facet_select,
                'values' => $values
            ];
        }


        if (empty($groups) && empty($currentFilter)) {
            return;
        }
        $this->BLayout->getView('catalogfields/filters')->selected_filters = $currentFilter;
        $this->BLayout->getView('catalogfields/filters')->groups = $groups;
        return $this->BLayout->getView('catalogfields/filters')->render();
    }

    public function onProductImportRow($args)
    {
        static $customFieldsOptions;

        $optionsHlp = $this->FCom_Core_Model_FieldOption;
        if (!$customFieldsOptions) {
            $customFieldsOptions = $optionsHlp->getListAssoc();
        }
        $config = $args['config'];
        $data = $args['data'];

        if (!$config['import']['custom_fields']['import']) {
            return;
        }
        //find intersection of custom fields with data fields
        $cfFields = $this->FCom_Core_Model_Field->getAllFields('field_code', 'product');
        $cfKeys = array_keys($cfFields);
        $dataKeys = array_keys($data);
        $cfIntersection = array_intersect($cfKeys, $dataKeys);

        if (!$cfIntersection) {
            return;
        }
        //get custom fields values from data
        foreach ($cfIntersection as $cfk) {
            /** @var FCom_Core_Model_Field $field */
            $field = $cfFields[$cfk];
            $dataValue = $data[$cfk];
            if (!$config['import']['custom_fields']['create_missing_options']
                || empty($customFieldsOptions[$field->id()])
                || in_array($dataValue, $customFieldsOptions[$field->id()])
            ) {
                continue;
            }
            //create missing custom field options
            try {
                $optionsHlp->create(['field_id' => $field->id(), 'label' => $dataValue])->save();
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    public function onProductImportAfterLoop($args)
    {
        $config = $args['config'];

        if ($config['import']['custom_fields']['import']
            && !empty($cfIntersection) && !empty($productIds) && !empty($cfFields)) {
            //get custom fields values from data
            $fieldIds = [];
            foreach ($cfIntersection as $cfk) {
                $field = $cfFields[$cfk];
                $fieldIds[] = $field->id();
            }

            //get or create product custom field
            $customsResult = $this->Sellvana_CatalogFields_Model_ProductField->orm()->where_in("product_id", $productIds)->find_many();
            foreach ($customsResult as $cus) {
                $customsResult[$cus->product_id] = $cus;
            }
            $productCustomFields = [];
            foreach ($productIds as $pId) {
                if (!empty($customFields[$pId])) {
                    $productCustomFields = $customFields[$pId];
                }
                $productCustomFields['_add_field_ids'] = implode(",", $fieldIds);
                $productCustomFields['product_id'] = $pId;
                if (!empty($customsResult[$pId])) {
                    $custom = $customsResult[$pId];
                } else {
                    $custom = $this->Sellvana_CatalogFields_Model_ProductField->create();
                }
                $custom->set($productCustomFields);
                $custom->save();
                unset($custom);
            }
            unset($customFields);
            unset($customsResult);
        }

    }

    public function onUpdateFieldsDueToInfo($args)
    {
        //$info = $args['info'];
        $object = $args['object'];
        $cfFields = $this->FCom_Core_Model_Field->getAllFields('field_code', 'product');
        $cfKeys = array_keys($cfFields);
        //$dataKeys = $info['first_row'];
        //$cfIntersection = array_intersect($cfKeys, $dataKeys);
        foreach ($cfKeys as $key) {
            if (!isset($object->fields['product.' . $key])) {
                $object->fields['product.' . $key] = ['pattern' => $key];
            }
        }
        /*
        if ($dataKeys) {
            foreach ($dataKeys as $f) {
                if (!isset($this->fields['product.'.$f])) {
                    $this->fields['product.'.$f] = array('pattern' => $f);
                }
            }
        }
         *
         */
    }

    public function onProductAfterLoad($args)
    {
        $this->Sellvana_CatalogFields_Model_ProductFieldData->collectModelsFieldData([$args['model']]);
    }

    public function onFindManyAfter($args)
    {
        /** @var BORM $orm */
        $orm = $args['orm'];
        if ($orm->get('_context') == 'catalog_products') {
            $this->Sellvana_CatalogFields_Model_ProductFieldData->collectModelsFieldData($args['result']);
        }
    }
}

