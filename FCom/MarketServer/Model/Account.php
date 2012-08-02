<?php

class FCom_MarketServer_Model_Account extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_marketserver_account';

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Model_IndexHelper
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public function getOptions($customerId)
    {
        return $this->orm()->where("customer_id", $customerId)->find_one();
    }
}
