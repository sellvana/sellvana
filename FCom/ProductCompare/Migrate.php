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
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'cookie_token' => 'varchar(40) default null',
                'customer_id' => 'int unsigned default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tSet}_customer" => "FOREIGN KEY (customer_id) REFERENCES {$tCustomer} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tSetItem, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'set_id' => 'int unsigned not null',
                'product_id' => 'int unsigned not null',
                'create_at' => 'datetime not null',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tSetItem}_set" => "FOREIGN KEY (set_id) REFERENCES {$tSet} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tSetItem}_product" => "FOREIGN KEY (product_id) REFERENCES {$tProduct} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);
    }
}