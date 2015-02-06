<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 * @package FCom_CustomerGroups
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_CustomerGroups_Model_Group $FCom_CustomerGroups_Model_Group
 * @property FCom_CustomerGroups_Model_TierPrice $FCom_CustomerGroups_Model_TierPrice
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */

class FCom_CustomerGroups_Migrate extends BClass
{
    public function install__0_1_2()
    {
        $tableCustomerGroup = $this->FCom_CustomerGroups_Model_Group->table();

        $this->BDb->ddlTableDef($tableCustomerGroup,
            [
                BDb::COLUMNS => [
                  'id'    => 'int(10) unsigned auto_increment',
                  'title' => 'varchar(100) not null',
                  'code'  => 'varchar(50) not null',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS    => [
                  'cg_code' => 'UNIQUE (code)'
                ],
            ]
        );

        $this->BDb->run("
        replace INTO `{$tableCustomerGroup}` (`id`, `title`, `code`)
        VALUES (1, 'General', 'general'), (2, 'NOT LOGGED IN', 'guest'), (3, 'Retailer', 'retailer')
        ");

        $this->BDb->ddlTableDef($this->FCom_Customer_Model_Customer->table(),
            [
                 BDb::COLUMNS => [
                     'customer_group' => 'int(10) unsigned null default null'
                 ],
                 BDb::CONSTRAINTS    => [
                     'group' => ['customer_group', $tableCustomerGroup],
                 ],
            ]
        );

        $tableTierPrices = $this->FCom_CustomerGroups_Model_TierPrice->table();

        $tableProduct = $this->FCom_Catalog_Model_Product->table();
        $this->BDb->ddlTableDef($tableTierPrices,
            [
                BDb::COLUMNS => [
                    'id'         => 'int(10) unsigned not null auto_increment',
                    'product_id' => 'int(10) unsigned not null',
                    'group_id'   => 'int(10) unsigned not null',
                    'base_price' => 'decimal(12,2) not null',
                    'sale_price' => 'decimal(12,2) not null',
                    'qty'        => 'int(10) unsigned not null default 1',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'UNQ_prod_group_qty' => 'UNIQUE (product_id, group_id, qty)',
                ], // should we add unique key from product_id + group_id + qty ???
                BDb::CONSTRAINTS => [
                    'product' => ['product_id', $tableProduct],
                    'group' => ['group_id', $tableCustomerGroup],
                ],
            ]
        );
        $this->BDb->run("
        replace INTO `{$tableCustomerGroup}` (`id`, `title`, `code`)
        VALUES (0, 'ALL', 'all')
        ");
    } // end install

    public function upgrade__0_1_0__0_1_1()
    {
        $tableTierPrices = $this->FCom_CustomerGroups_Model_TierPrice->table();

        $tableProduct = $this->FCom_Catalog_Model_Product->table();
        $tableCustGroups = $this->FCom_CustomerGroups_Model_Group->table();
        $this->BDb->ddlTableDef($tableTierPrices,
            [
                BDb::COLUMNS => [
                    'id'         => 'int(10) unsigned not null auto_increment',
                    'product_id' => 'int(10) unsigned not null',
                    'group_id'   => 'int(10) unsigned not null',
                    'base_price' => 'decimal(12,2) not null',
                    'sale_price' => 'decimal(12,2) not null',
                    'qty'        => 'int(10) unsigned not null default 1',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'UNQ_prod_group_qty' => 'UNIQUE (product_id, group_id, qty)',
                ], // should we add unique key from product_id + group_id + qty ???
                BDb::CONSTRAINTS => [
                    'product' => ['product_id', $tableProduct],
                    'group' => ['group_id', $tableCustGroups],
                ],
            ]
        );
//        $conn = $this->BDb->connect();
//
//        /*
//         * If we use tier prices, we should probably populate them?
//         */
//        $st = $conn->query("SELECT p.id, p.base_price FROM {$tableProduct}");
//        $gid = $this->FCom_CustomerGroups_Model_Group->orm()->where('code', 'guest')->find_one()->id;
//        $ins = $conn->prepare("INSERT INTO `$tableTierPrices`
//        (product_id, group_id, base_price, sale_price, qty)
//        VALUES(?, ?, ?, ?)");
//        foreach ($st as $row) {
//            $data = array(
//                $row['id'],
//                $gid,
//                $row['base_price,'],
//                $row['base_price,'],
//                1
//            );
//            $ins->execute($data);
//        }
//        $conn->commit();
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tableCustomerGroup = $this->FCom_CustomerGroups_Model_Group->table();
        $this->BDb->run("
        replace INTO `{$tableCustomerGroup}` (`id`, `title`, `code`)
        VALUES (0, 'ALL', 'all')
        ");
    }
}
