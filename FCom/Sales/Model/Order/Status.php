<?php

class FCom_Sales_Model_Order_Status extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_status';
    protected static $_origClass = __CLASS__;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_Order_Item
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(get_called_class(), $args, !$new);
    }

    public function statusNew()
    {
        return self::orm()->where('code', 'new')->find_one();
    }
    public function statusPending()
    {
        return self::orm()->where('code', 'pending')->find_one();
    }
    public function statusPaid()
    {
        return self::orm()->where('code', 'paid')->find_one();
    }
    public function statusList()
    {
        return self::orm()->find_many();
    }
}