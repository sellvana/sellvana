<?php

class FCom_MarketServer_Model_Account extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_marketserver_account';

    public function getOptions($customerId)
    {
        return $this->orm()->where("customer_id", $customerId)->find_one();
    }
}
