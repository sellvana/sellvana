<?php

/**
 * Class Sellvana_CustomerFields_Model_CustomerFieldData
 *
 */
class Sellvana_CustomerFields_Model_CustomerFieldData extends FCom_Core_Model_Abstract_FieldData
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_customer_field_data';
    protected static $_fieldType = 'customer';
    protected static $_mainModel = 'Sellvana_Customer_Model_Customer';
    protected static $_mainModelKeyField = 'customer_id';
}
