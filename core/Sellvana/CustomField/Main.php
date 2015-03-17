<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomField_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_CustomField_Model_Field $Sellvana_CustomField_Model_Field
 * @property Sellvana_CustomField_Model_ProductField $Sellvana_CustomField_Model_ProductField
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_CustomField_Main extends BClass
{
    protected $_types;
    protected $_disabled;

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'custom_fields' => 'Custom Fields'
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
     * @param $args
     */
    public function onProductOrm($args)
    {
        if ($this->_disabled) {
            return;
        }
        $tP = $args['orm']->table_alias();
        $args['orm']
            ->select($tP . '.*')
            ->left_outer_join('Sellvana_CustomField_Model_ProductField', ['pcf.product_id', '=', $tP . '.id'], 'pcf')
        ;
        $fields = $this->Sellvana_CustomField_Model_Field->fieldsInfo('product', true);
        $args['orm']->select($fields);
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
            /** @var Sellvana_CustomField_Model_ProductVariant $model */
            $model->onAfterLoad();
        }
    }

    /**
     * @param $args
     * @throws BException
     */
    public function onProductAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = $this->Sellvana_CustomField_Model_Field->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = $this->Sellvana_CustomField_Model_ProductField->load($p->id, 'product_id');
            if (!$custom) {
                $custom = $this->Sellvana_CustomField_Model_ProductField->create();
            }
            $dataCustomKeys = array_intersect($fields, array_keys($data));
            $dataCustom = [];
            foreach ($dataCustomKeys as $key) {
                $dataCustom[$key] = $data[$key];
            }
            //print_r($dataCustom);exit;
            $custom->set($dataCustom)->set('product_id', $p->id())->save();
        }
        // not deleting to preserve meta info about fields
    }

    /**
     * @param $args
     * @return mixed|string
     */
    public function hookCustomFieldFilters($args)
    {
        $category = false;
        if (is_object($args['category'])) {
            $category = $args['category'];
        }

        $customFields = $this->Sellvana_CustomField_Model_Field->orm()
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
                /** @var Sellvana_CustomField_Model_Field $field */
                $field = $this->Sellvana_CustomField_Model_Field->orm()->where('field_code', $fkey)->find_one();
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
        $this->BLayout->view('customfields/filters')->selected_filters = $currentFilter;
        $this->BLayout->view('customfields/filters')->groups = $groups;
        return $this->BLayout->view('customfields/filters')->render();
    }
}

