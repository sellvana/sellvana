<?php

/**
 * Created by pp
 *
 * @property Sellvana_CustomerFields_Model_Field             $Sellvana_CustomerFields_Model_Field
 * @property Sellvana_CustomerFields_Model_CustomerFieldData $Sellvana_CustomerFields_Model_CustomerFieldData
 * @project sellvana_core
 */
class Sellvana_CustomerFields_Frontend extends BClass
{
    public function hookEdit($args)
    {
        // todo load all custom fields allowed in edit form and render them

        $customerFields = $this->Sellvana_CustomerFields_Model_Field
            ->orm()
            ->where("account_edit", 1)->find_many_assoc();
        if (!$customerFields) {
            return '';
        }
        $fieldNames = [];
        /** @var Sellvana_CustomerFields_Model_Field $cf */
        foreach ($customerFields as $cf) {
            $fieldNames[$cf->get('field_code')] = $cf;
        }
        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer   = $args['customer'];
        //$cId        = $customer->id();
        $this->Sellvana_CustomerFields_Model_CustomerFieldData
            ->collectCustomersFieldData([$customer]);

        $data       = [];
        //if (!empty($customData[$cId])) {
        //    $customData = $customData[$cId];
        //}
        /** @var Sellvana_CustomerFields_Model_Field $field */
        foreach ($fieldNames as $fn => $field) {
            //$fId = $field->id();
            //$value = null;
            $value = $customer->get($fn);
            //if (isset($customData[$fId])) {
            //    /** @var Sellvana_CustomerFields_Model_CustomerFieldData $cd */
            //    $cd    = $customData[$fId];
            //    $value = $cd->getCustomerFieldValue();
            //}
            $data[$fn] = ['value' => $value, 'field' => $field];
        }

        return $this->BLayout->view('customer/hook/customer-edit')
                             ->set(['customer' => $customer, 'data' => $data])
                             ->render();
    }

    public function hookRegister()
    {
        $customerFields = $this->Sellvana_CustomerFields_Model_Field
            ->orm()
            ->where("register_form", 1)->find_many();

        $render = $this->BLayout->view('customer/hook/customer-register')
                                ->set(['fields' => $customerFields])
                                ->render();

        return $render;
    }
}
