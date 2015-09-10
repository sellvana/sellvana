<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogFields_Admin
 *
 * @property Sellvana_CustomerFields_Model_Field             $Sellvana_CustomerFields_Model_Field
 * @property Sellvana_CustomerFields_Model_FieldOption       $Sellvana_CustomerFields_Model_FieldOption
 * @property Sellvana_CustomerFields_Model_CustomerFieldData $Sellvana_CustomerFields_Model_CustomerFieldData
 * @property Sellvana_Customer_Model_Customer                $Sellvana_Customer_Model_Customer
 */
class Sellvana_CustomerFields_Admin extends BClass
{
    public function onCustomerGridColumns($args)
    {
        /** @var Sellvana_CatalogFields_Model_Field[] $fields */
        $fields = $this->Sellvana_CustomerFields_Model_Field->orm('f')->find_many();
        foreach ($fields as $f) {
            $col = ['label' => $f->field_name, 'index' => 'pcf.' . $f->field_name, 'hidden' => true];
            if ($f->admin_input_type == 'select') {
                $col['options'] = $this->Sellvana_CustomerFields_Model_FieldOption->orm()
                    ->where('field_id', $f->id)
                    ->find_many_assoc(stripos($f->table_field_type, 'varchar') === 0 ? 'label' : 'id', 'label');
            }
            $args['columns'][$f->field_code] = $col;
        }
    }

    public function onCustomerFormViewBefore()
    {
        /** @var Sellvana_Customer_Model_Customer $c */
        $c = $this->BLayout->view('admin/form')->get('model');

        if (!$c) {
            return;
        }

        $fieldsOptions = [];
        $fields = $this->Sellvana_CustomerFields_Model_CustomerFieldData->fetchCustomersFieldData([$c->id()]);
        if ($fields) {
            $fieldIds = $this->BUtil->arrayToOptions($fields, 'id');
            $fieldOptionsAll = $this->Sellvana_CustomerFields_Model_FieldOption->orm()->where_in("field_id", $fieldIds)
                ->order_by_asc('field_id')->order_by_asc('label')->find_many();
            foreach ($fieldOptionsAll as $option) {
                $fieldsOptions[$option->get('field_id')][] = $option;
            }
        }
        $view = $this->BLayout->view('customerfields/customers/fields-partial');
        $view->set('model', $c)->set('fields', $fields)->set('fields_options', $fieldsOptions);
    }

    public function onCustomerFormPostAfter($args)
    {
        /** @var Sellvana_Customer_Model_Customer $model */
        $model = $args['model'];
        $customFields = $this->BRequest->post('custom_fields');

        if (!empty($customFields)) {
            $fields = $this->BUtil->fromJson($customFields);
            foreach ($fields as $f) {
                if (isset($f['field_code'])) {
                    $k = $f['field_code'];
                    $v = $f['value'];
                    $model->set($k, $v);
                }
            }

            $model->setData('custom_fields', $customFields)->save();
        }
    }
}
