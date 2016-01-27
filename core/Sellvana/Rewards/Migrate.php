<?php

/**
 * Class Sellvana_Rewards_Migrate
 *
 * @property Sellvana_Rewards_Model_Balance $Sellvana_Rewards_Model_Balance
 * @property Sellvana_Rewards_Model_Transaction $Sellvana_Rewards_Model_Transaction
 * @property Sellvana_Rewards_Model_Rule Sellvana_Rewards_Model_Rule
 * @property Sellvana_Rewards_Model_RuleProduct Sellvana_Rewards_Model_RuleProduct
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Rewards_Migrate extends BClass
{
    public function install__0_5_0_0()
    {
        $tBalance = $this->Sellvana_Rewards_Model_Balance->table();
        $tTransaction = $this->Sellvana_Rewards_Model_Transaction->table();
        $tRule = $this->Sellvana_Rewards_Model_Rule->table();
        $tRuleProduct = $this->Sellvana_Rewards_Model_RuleProduct->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $tOrder = $this->Sellvana_Sales_Model_Order->table();

        $this->BDb->ddlTableDef($tBalance, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_id' => 'int unsigned not null',
                'points' => 'int default 0',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime default null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
            ],
        ]);

        $this->BDb->ddlTableDef($tTransaction, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'balance_id' => 'int unsigned not null',
                'order_id' => 'int unsigned default null',
                'product_id' => 'int unsigned default null',
                'event' => 'varchar(20) not null',
                'points' => 'int not null',
                'amount' => 'decimal(12,2) default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime default null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'balance' => ['balance_id', $tBalance],
                'order' => ['order_id', $tOrder, 'id', 'CASCADE', 'SET NULL'],
                'product' => ['product_id', $tProduct, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tRule, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(255) not null',
                'valid_from' => 'datetime default null',
                'valid_to' => 'datetime default null',
                'last_recalculated_at' => 'datetime default null',
                'create_at' => 'datetime default null',
                'update_at' => 'datetime default null',
                'data_serialized' => 'text default null',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlTableDef($tRuleProduct, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'rule_id' => 'int unsigned not null',
                'product_id' => 'int unsigned not null',
                'points' => 'int not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'rule' => ['rule_id', $tRule],
                'product' => ['product_id', $tProduct],
            ],
        ]);

    }
}