<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerGroups_Main
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_CustomerGroups_Main extends BClass
{
    public function onCustomerBeforeSave($args)
    {
        $defCustGroup = $this->BConfig->get('modules/Sellvana_CustomerGroups/default_group_id');
        $args['model']->set('customer_group', $defCustGroup);
    }
}