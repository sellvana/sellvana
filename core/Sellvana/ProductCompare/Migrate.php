<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ProductCompare_Migrate
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_ProductCompare_Model_Set $Sellvana_ProductCompare_Model_Set
 * @property Sellvana_ProductCompare_Model_SetItem $Sellvana_ProductCompare_Model_SetItem
 */

class Sellvana_ProductCompare_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tSet = $this->Sellvana_ProductCompare_Model_Set->table();
        $tSetItem = $this->Sellvana_ProductCompare_Model_SetItem->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tSet, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'cookie_token' => 'varchar(40) default null',
                'customer_id' => 'int unsigned default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
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
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'set' => ['set_id', $tSet],
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
}