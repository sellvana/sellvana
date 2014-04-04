<?php
/**
 * Created by pp
 * @project fulleron
 * @package FCom_CustomerGroups
 */

class FCom_CustomerGroups_Migrate extends BClass
{
    public function install__0_1_2()
    {
        $tableCustomerGroup = FCom_CustomerGroups_Model_Group::table();

        BDb::ddlTableDef($tableCustomerGroup,
            array(
                'COLUMNS' => array(
                  'id'    => 'int(10) unsigned auto_increment',
                  'title' => 'varchar(100) not null',
                  'code'  => 'varchar(50) not null',
                ),
                'PRIMARY' => '(id)',
                'KEYS'    => array(
                  'cg_code' => 'UNIQUE (code)'
                ),
            )
        );

        BDb::run("
        replace INTO `{$tableCustomerGroup}` (`id`, `title`, `code`)
        VALUES (1, 'General', 'general'), (2, 'NOT LOGGED IN', 'guest'), (3, 'Retailer', 'retailer')
        ");

        BDb::ddlTableDef(FCom_Customer_Model_Customer::table(),
            array(
                 'COLUMNS' => array(
                     'customer_group' => 'int(10) unsigned null default null'
                 ),
                 'CONSTRAINTS'    => array(
                     'fk_customer_group' => "FOREIGN KEY (customer_group) REFERENCES {$tableCustomerGroup}(id) ON DELETE CASCADE ON UPDATE CASCADE"
                 ),
            )
        );

        $tableTierPrices = FCom_CustomerGroups_Model_TierPrice::table();

        $tableProduct = FCom_Catalog_Model_Product::table();
        BDb::ddlTableDef($tableTierPrices,
            array(
                'COLUMNS' => array(
                    'id'         => 'int(10) unsigned not null auto_increment',
                    'product_id' => 'int(10) unsigned not null',
                    'group_id'   => 'int(10) unsigned not null',
                    'base_price' => 'decimal(12,2) not null',
                    'sale_price' => 'decimal(12,2) not null',
                    'qty'        => 'int(10) unsigned not null default 1',
                ),
                'PRIMARY' => '(id)',
                'KEYS' => array(
                    'UNQ_prod_group_qty' => 'UNIQUE (product_id, group_id, qty)',
                ), // should we add unique key from product_id + group_id + qty ???
                'CONSTRAINTS' => array(
                    "FK_{$tableTierPrices}_product" => "FOREIGN KEY (product_id) REFERENCES {$tableProduct}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    "FK_{$tableTierPrices}_group" => "FOREIGN KEY (group_id) REFERENCES {$tableCustomerGroup}(id) ON DELETE CASCADE ON UPDATE CASCADE"
                ),
            )
        );
        BDb::run("
        replace INTO `{$tableCustomerGroup}` (`id`, `title`, `code`)
        VALUES (0, 'ALL', 'all')
        ");
    } // end install

    public function upgrade__0_1_0__0_1_1()
    {
        $tableTierPrices = FCom_CustomerGroups_Model_TierPrice::table();

        $tableProduct = FCom_Catalog_Model_Product::table();
        $tableCustGroups = FCom_CustomerGroups_Model_Group::table();
        BDb::ddlTableDef($tableTierPrices,
            array(
                'COLUMNS' => array(
                    'id'         => 'int(10) unsigned not null auto_increment',
                    'product_id' => 'int(10) unsigned not null',
                    'group_id'   => 'int(10) unsigned not null',
                    'base_price' => 'decimal(12,2) not null',
                    'sale_price' => 'decimal(12,2) not null',
                    'qty'        => 'int(10) unsigned not null default 1',
                ),
                'PRIMARY' => '(id)',
                'KEYS' => array(
                    'UNQ_prod_group_qty' => 'UNIQUE (product_id, group_id, qty)',
                ), // should we add unique key from product_id + group_id + qty ???
                'CONSTRAINTS' => array(
                    "FK_{$tableTierPrices}_product" => "FOREIGN KEY (product_id) REFERENCES {$tableProduct}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    "FK_{$tableTierPrices}_group" => "FOREIGN KEY (group_id) REFERENCES {$tableCustGroups}(id) ON DELETE CASCADE ON UPDATE CASCADE"
                ),
            )
        );
//        $conn = BDb::connect();
//
//        /*
//         * If we use tier prices, we should probably populate them?
//         */
//        $st = $conn->query("SELECT p.id, p.base_price FROM {$tableProduct}");
//        $gid = FCom_CustomerGroups_Model_Group::orm()->where('code', 'guest')->find_one()->id;
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
        $tableCustomerGroup = FCom_CustomerGroups_Model_Group::table();
        BDb::run("
        replace INTO `{$tableCustomerGroup}` (`id`, `title`, `code`)
        VALUES (0, 'ALL', 'all')
        ");
    }
}
