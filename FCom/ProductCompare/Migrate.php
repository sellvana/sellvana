<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductCompare_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tSet = $this->FCom_ProductCompare_Model_Set->table();
        $tSetItem = $this->FCom_ProductCompare_Model_SetItem->table();
        $tCustomer = $this->FCom_Customer_Model_Customer->table();
        $tProduct = $this->FCom_Catalog_Model_Product->table();

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
}