<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_RecentlyViewed_Migrate
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_RecentlyViewed_Model_History $Sellvana_RecentlyViewed_Model_History
 */

class Sellvana_RecentlyViewed_Migrate extends BClass
{
    public function install__0_5_0()
    {
        $tHistory = $this->Sellvana_RecentlyViewed_Model_History->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'cookie_token' => 'varbinary(40) default null',
                'customer_id' => 'int unsigned default null',
                'product_id' => 'int unsigned not null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'data_serialized' => 'text default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_token_customer_update' => '(cookie_token, customer_id, update_at)',
            ],
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
                'product' => ['product_id', $tProduct],
            ],
        ]);
    }
}