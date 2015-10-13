<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_CustomerGroupTax extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_customer_group_tax';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['customer_group_id', 'customer_class_id'],
        'related'    => [
            'customer_group_id'       => 'Sellvana_CustomerGroups_Model_Group.id',
            'customer_class_id' => 'Sellvana_SalesTax_Model_CustomerClass.id'
        ],
    ];

    public function getCustomerTaxClassIds($group)
    {
        return $this->orm()->where('customer_id', $group->id())->find_many_assoc('id', 'customer_class_id');
    }

    public function getUseGroupTax($customer)
    {
        return '1';
    }
}
