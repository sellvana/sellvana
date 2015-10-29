<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerFields_Admin_Controller_Customers
 *
 * @property Sellvana_CustomerFields_Model_CustomerFieldData $Sellvana_CustomerFields_Model_CustomerFieldData
 * @property Sellvana_CustomerFields_Model_FieldOption       $Sellvana_CustomerFields_Model_FieldOption
 * @property Sellvana_Customer_Model_Customer                $Sellvana_Customer_Model_Customer
 * @property Sellvana_CustomerFields_Model_Field             $Sellvana_CustomerFields_Model_Field
 * @property FCom_Core_Main                                  $FCom_Core_Main
 */
class Sellvana_CustomerFields_Admin_Controller_Customers extends FCom_Admin_Controller_Abstract
{
    public function action_field_remove()
    {
        $id = $this->BRequest->param('id', true);
        $p = $this->Sellvana_Customer_Model_Customer->load($id);
        if (!$p) {
            return;
        }
        $hide_field = $this->BRequest->param('hide_field', true);
        if (!$hide_field) {
            return;
        }
        $this->Sellvana_CustomerFields_Model_CustomerFieldData->removeField($p, $hide_field);
        $this->BResponse->json('');
    }

    public function action_fields_partial()
    {
        $id = $this->BRequest->param('id', true);
        $p = $this->Sellvana_Customer_Model_Customer->load($id);
        if (!$p) {
            $p = $this->Sellvana_Customer_Model_Customer->create();
        }

        $fields_options = [];
        $fields = $this->Sellvana_CustomerFields_Model_CustomerFieldData->customerFields($p, $this->BRequest->request());
        foreach ($fields as $field) {
            $fields_options[$field->id()] = $this->Sellvana_CustomerFields_Model_FieldOption->orm()
                ->where("field_id", $field->id())->find_many();
        }

        $view = $this->view('customerfields/customers/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fields_options);
        $this->BLayout->setRootView('customerfields/customers/fields-partial');
        $this->BResponse->render();
    }

    public function getInitialData($model)
    {
        $customFields = $model->getData('custom_fields');
        return !isset($customFields) ? -1 : $customFields;
    }

    public function action_get_field()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        $field = $this->Sellvana_CustomerFields_Model_Field->load($id);
        $options = $this->Sellvana_CustomerFields_Model_FieldOption->getListAssocById($field->id());
        $this->BResponse->json(['id' => $field->id(), 'field_name' => $field->field_name, 'field_code' => $field->field_code,
            'admin_input_type' => $field->admin_input_type, 'multilang' => $field->multilanguage,
            'options' => $options, 'required' => $field->required]);
    }

    public function action_save__POST()
    {
        try {
            $data = $this->BRequest->post();
            $customerId = $data['id'];
            $json = $data['json'];
            $hlp = $this->Sellvana_CustomerFields_Model_CustomerFieldData;
            $res = $hlp->load($customerId, 'product_id');
            if (!$res) {
                $hlp->create(['product_id' => $customerId, '_data_serialized' => $json])->save();
                $status = 'Successfully saved.';
            } else {
                $res->set('_data_serialized', $json)->save();
                $status = 'Successfully updated.';
            }
        } catch (Exception $e) {
            $status = false;
        }
        $this->BResponse->json(['status' => $status]);
    }

    public function action_get_fields__POST()
    {
        try {
            $res = [];
            $data = $this->BRequest->post();
            $ids = explode(',', $data['ids']);
            $optionsHlp = $this->Sellvana_CustomerFields_Model_FieldOption;
            $fields = $this->Sellvana_CustomerFields_Model_Field->orm()->where('id', $ids)->find_many_assoc();
            foreach ($fields as $id => $field) {
                $res[] = [
                    'id' => $id,
                    'name' => $field->field_name,
                    'label' => $field->frontend_label,
                    'input_type' => $field->admin_input_type,
                    'options' => join(',', array_keys($optionsHlp->getListAssocById($id))),
                    'required' => $field->required,
                    'field_code' => $field->field_code,
                    'position' => ''
                ];
            }
        } catch (Exception $e) {
            $res = ['error' => $e->getMessage()];
        }

        $this->BResponse->json($res);
    }

    public function getFieldTypes()
    {
        $f = $this->Sellvana_CustomerFields_Model_Field;
        return $f->fieldOptions('table_field_type');
    }

    public function getAdminInputTypes()
    {
        $f = $this->Sellvana_CustomerFields_Model_Field;
        return $f->fieldOptions('admin_input_type');
    }
}
