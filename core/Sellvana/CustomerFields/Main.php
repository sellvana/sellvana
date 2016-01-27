<?php

/**
 * Class Sellvana_CustomerFields_Main
 *
 * @property FCom_Admin_Model_Role                           $FCom_Admin_Model_Role
 * @property Sellvana_CustomerFields_Model_Field             $Sellvana_CustomerFields_Model_Field
 * @property Sellvana_CustomerFields_Model_CustomerFieldData $Sellvana_CustomerFields_Model_CustomerFieldData
 * @property Sellvana_Customer_Model_Customer                $Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerFields_Model_FieldOption       $Sellvana_CustomerFields_Model_FieldOption
 */
class Sellvana_CustomerFields_Main extends BClass
{
    protected $_types;
    protected $_disabled;

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'customer_fields' => 'Customer Custom Fields'
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

    ///**
    // * @param $args
    // */
    //public function onCustomerOrm($args)
    //{
    //    if ($this->_disabled) {
    //        return;
    //    }
    //    /** @var BORM $orm */
    //    $orm = $args['orm'];
    //    $tC = $orm->table_alias();
    //    $orm->select($tC . '.*')
    //        ->left_outer_join('Sellvana_CustomerFields_Model_CustomerFieldData', ['ccf.customer_id', '=', $tC . '.id'], 'ccf');
    //    $fields = $this->Sellvana_CustomerFields_Model_Field->getAllFields();
    //    $orm->select($fields);
    //}

    /**
     * @param $args
     * @throws BException
     */
    public function onCustomerAfterSave($args)
    {
        /** @var Sellvana_Customer_Model_Customer $c */
        $c      = $args['model'];
        $this->Sellvana_CustomerFields_Model_CustomerFieldData->saveCustomersFieldData([$c]);
        //$data   = $c->as_array();
        //$fields = $this->Sellvana_CustomerFields_Model_Field->fieldsInfo('customer', true);
        //if (array_intersect($fields, array_keys($data))) {
        //    $custom = $this->Sellvana_CustomerFields_Model_CustomerFieldData->load($c->id(), 'customer_id');
        //    if (!$custom) {
        //        $custom = $this->Sellvana_CustomerFields_Model_CustomerFieldData->create();
        //    }
        //    $dataCustomKeys = array_intersect($fields, array_keys($data));
        //    $dataCustom     = [];
        //    foreach ($dataCustomKeys as $key) {
        //        $dataCustom[$key] = $data[$key];
        //    }
        //    //print_r($dataCustom);exit;
        //    $custom->set($dataCustom)->set('customer_id', $c->id())->save();
        //}
        // not deleting to preserve meta info about fields
    }

    /**
     * @return mixed|string
     */
    public function hookCustomFieldFilters()
    {
        $customFields = $this->Sellvana_CustomerFields_Model_Field
                             ->orm()
                             ->where_in('facet_select', ['Inclusive', 'Exclusive'])
                             ->where('frontend_show', 1)
                             ->order_by_asc('sort_order')
                             ->find_many();

        if (!$customFields) {
            return null;
        }

        $filter         = $this->BRequest->get('f');
        $currentFilter  = [];
        $excludeFilters = [];
        if (!empty($filter)) {
            foreach ($filter as $fkey => $fval) {
                $fkey = urldecode($fkey);
                /** @var Sellvana_CustomerFields_Model_Field $field */
                $field = $this->Sellvana_CustomerFields_Model_Field
                            ->orm()
                            ->where('field_code', $fkey)
                            ->find_one();
                $currentFilter[$field->frontend_label][] = [
                    'key'          => $field->field_code,
                    'facet_select' => $field->facet_select,
                    'value'        => $fval,
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
                $customerOrm = $this->Sellvana_Customer_Model_Customer->orm();
            $customers = $customerOrm->where_not_equal($cf->field_code, '')->group_by($cf->field_code)->find_many();
            if (empty($customers)) {
                continue;
            }
            $values = [];
            foreach ($customers as $c) {
                if (isset($excludeFilters[$cf->frontend_label]) &&
                    in_array($c->{$cf->field_code}, $excludeFilters[$cf->frontend_label])
                ) {
                    continue;
                }
                $values[] = $c->{$cf->field_code};
            }
            if (empty($values)) {
                continue;
            }
            $groups[$cf->frontend_label] = [
                'key'          => $cf->field_code,
                'facet_select' => $cf->facet_select,
                'values'       => $values
            ];
        }

        if (empty($groups) && empty($currentFilter)) {
            return null;
        }
        $this->BLayout->view('customerfields/filters')->set(['selected_filters' => $currentFilter, 'groups' => $groups]);

        return $this->BLayout->view('customerfields/filters')->render();
    }

    public function onCustomerImportRow($args)
    {
        static $customFieldsOptions;

        $optionsHlp = $this->Sellvana_CustomerFields_Model_FieldOption;
        if (!$customFieldsOptions) {
            $customFieldsOptions = $optionsHlp->getListAssoc();
        }
        $config = $args['config'];
        $data   = $args['data'];

        if (!$config['import']['custom_fields']['import']) {
            return;
        }

        //find intersection of custom fields with data fields
        $cfFields       = $this->Sellvana_CustomerFields_Model_Field->getAllFields();
        $cfKeys         = array_keys($cfFields);
        $dataKeys       = array_keys($data);
        $cfIntersection = array_intersect($cfKeys, $dataKeys);

        if (!$cfIntersection) {
            return;
        }
        //get custom fields values from data
        foreach ($cfIntersection as $cfk) {
            /** @var Sellvana_CustomerFields_Model_Field $field */
            $field     = $cfFields[$cfk];
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
            } catch(Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    public function onCustomerImportAfterLoop($args)
    {
        $config = $args['config'];
//todo findout where do $cfIntersection, $customerIds and $cfFields come from!!!
        if ($config['import']['custom_fields']['import']
            && !empty($cfIntersection) && !empty($customerIds) && !empty($cfFields)
        ) {
            // get custom fields values from data
            $fieldIds = [];
            foreach ($cfIntersection as $cfk) {
                /** @var Sellvana_CustomerFields_Model_Field $field */
                $field      = $cfFields[$cfk];
                $fieldIds[] = $field->id();
            }

            // get or create product custom field
            $customsResult = $this->Sellvana_CustomerFields_Model_CustomerFieldData->orm()
                                                                             ->where_in("customer_id", $customerIds)
                                                                             ->find_many();
            foreach ($customsResult as $cus) {
                $customsResult[$cus->customer_id] = $cus;
            }
            $customerCustomFields = [];
            foreach ($customerIds as $cId) {
                if (!empty($customFields[$cId])) {
                    $customerCustomFields = $customFields[$cId];
                }
                $customerCustomFields['customer_id']     = $cId;
                if (!empty($customsResult[$cId])) {
                    $custom = $customsResult[$cId];
                } else {
                    $custom = $this->Sellvana_CustomerFields_Model_CustomerFieldData->create();
                }
                $custom->set($customerCustomFields);
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
        $object   = $args['object'];
        $cfFields = $this->Sellvana_CustomerFields_Model_Field->getAllFields();
        $cfKeys   = array_keys($cfFields);
        //$dataKeys = $info['first_row'];
        //$cfIntersection = array_intersect($cfKeys, $dataKeys);
        foreach ($cfKeys as $key) {
            if (!isset($object->fields['customer.' . $key])) {
                $object->fields['customer.' . $key] = ['pattern' => $key];
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
}

