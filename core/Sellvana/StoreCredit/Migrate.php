<?php

/**
 * Class Sellvana_StoreCredit_Migrate
 *
 * @property Sellvana_StoreCredit_Model_Balance $Sellvana_StoreCredit_Model_Balance
 * @property Sellvana_StoreCredit_Model_Transaction $Sellvana_StoreCredit_Model_Transaction
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_StoreCredit_Migrate extends BClass
{
    public function install__0_5_1_0()
    {
        $tBalance = $this->Sellvana_StoreCredit_Model_Balance->table();
        $tTransaction = $this->Sellvana_StoreCredit_Model_Transaction->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tOrder = $this->Sellvana_Sales_Model_Order->table();

        $this->BDb->ddlTableDef($tBalance, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_id' => 'int unsigned not null',
                'amount' => 'decimal(12,2) default 0',
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
                'event' => 'varchar(20) not null',
                'amount' => 'decimal(12,2) not null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime default null',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'balance' => ['balance_id', $tBalance],
                'order' => ['order_id', $tOrder, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);
    }

    public function upgrade__0_5_0_0__0_5_1_0()
    {
        $tTransaction = $this->Sellvana_StoreCredit_Model_Transaction->table();
        $tOrder = $this->Sellvana_Sales_Model_Order->table();

        $this->BDb->ddlTableDef($tTransaction, [
            BDb::COLUMNS => [
                'order_id' => 'int unsigned default null',
            ],
            BDb::CONSTRAINTS => [
                'order' => ['order_id', $tOrder, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);
    }
}