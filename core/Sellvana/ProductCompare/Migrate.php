<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ProductCompare_Migrate
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_ProductCompare_Model_Set $Sellvana_ProductCompare_Model_Set
 * @property Sellvana_ProductCompare_Model_SetItem $Sellvana_ProductCompare_Model_SetItem
 * @property Sellvana_ProductCompare_Model_History $Sellvana_ProductCompare_Model_History
 */

class Sellvana_ProductCompare_Migrate extends BClass
{
    public function install__0_5_2_0()
    {
        $tSet = $this->Sellvana_ProductCompare_Model_Set->table();
        $tSetItem = $this->Sellvana_ProductCompare_Model_SetItem->table();
        $tHistory = $this->Sellvana_ProductCompare_Model_History->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tSet, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'cookie_token' => 'varchar(40) default null',
                'customer_id' => 'int unsigned default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
            ],
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
            ],
        ]);

        $this->BDb->ddlTableDef($tSetItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'set_id' => 'int unsigned not null',
                'product_id' => 'int unsigned not null',
                'create_at' => 'datetime not null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'set' => ['set_id', $tSet],
                'product' => ['product_id', $tProduct],
            ],
        ]);

        $this->BDb->ddlTableDef($tHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'cookie_token' => 'varchar(40) default null',
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

    public function upgrade__0_1_0__0_1_1()
    {
        $tSet = $this->Sellvana_ProductCompare_Model_Set->table();
        $tSetItem = $this->Sellvana_ProductCompare_Model_SetItem->table();

        $this->BDb->ddlTableDef($tSet, [
            BDb::COLUMNS => [
                'data_serialized' => 'text',
            ],
        ]);
        $this->BDb->ddlTableDef($tSetItem, [
            BDb::COLUMNS => [
                'data_serialized' => 'text',
            ],
        ]);
    }

    public function upgrade__0_5_0_0__0_5_1_0()
    {
        $tHistory = $this->Sellvana_ProductCompare_Model_History->table();
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

    public function upgrade__0_5_1_0__0_5_2_0()
    {
        $tSet = $this->Sellvana_ProductCompare_Model_Set->table();
        $tHistory = $this->Sellvana_ProductCompare_Model_History->table();
        
        $this->BDb->ddlTableDef($tSet, [
            BDb::COLUMNS => [
                'cookie_token' => 'varchar(40)',
            ],
        ]);
        $this->BDb->ddlTableDef($tHistory, [
            BDb::KEYS => [
                'UNQ_cookie_token' => BDb::DROP,
            ],
        ]);
    }
}