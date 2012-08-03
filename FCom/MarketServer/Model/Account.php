<?php

class FCom_MarketServer_Model_Account extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_marketserver_account';

    public function getOptions($customerId)
    {
        return $this->orm()->where("customer_id", $customerId)->find_one();
    }

    static public function install()
    {
        $accountTable = FCom_MarketServer_Model_Account::table();
        BDb::run( "
            CREATE TABLE IF NOT EXISTS {$accountTable} (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `customer_id` INT (11) UNSIGNED NOT NULL,
            `site_url` VARCHAR( 1024 ) NOT NULL DEFAULT '',
            `token` char(40) NOT NULL
            ) ENGINE = InnoDB;
         ");

    }
}
