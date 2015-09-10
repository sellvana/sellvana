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
            ->where("account_edit", 1)->find_many();
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
        $customData = $this->Sellvana_CustomerFields_Model_CustomerFieldData
            ->orm()
            ->select(array_keys($fieldNames))
            ->where('customer_id', $customer->id())
            ->find_one();

        $data       = [];
        if ($customData) {
            foreach ($fieldNames as $fn => $field) {
                $data[$fn] = ['value' => $customData->get($fn), 'field' => $field];
            }

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
