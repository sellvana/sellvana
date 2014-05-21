<?php

class FCom_Stock_Model_Sku extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_stock_sku';
    static protected $_origClass = __CLASS__;
    public static function backOrders()
    {
        return [
            "NOT_BACK_ORDERS"         => BLocale::i()->_("No Back Orders"),
            "ALLOW_QUANTITY_BELOW" => BLocale::i()->_("Allow Quantity Below 0")
        ];
    }
}
