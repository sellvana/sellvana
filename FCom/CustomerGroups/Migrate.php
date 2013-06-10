<?php
/**
 * Created by pp
 * @project fulleron
 * @package FCom_CustomerGroups
 */

class FCom_CustomerGroups_Migrate
    extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
    }

    public function install()
    {
        $tableCustomerGroup = FCom_CustomerGroups_Model_Group::table();

        BDb::ddlTableDef($tableCustomerGroup,
            array(
                'COLUMNS' => array(
                  'id'    => 'int(10) unsigned primary key auto_increment',
                  'title' => 'varchar(100) not null',
                  'code'  => 'varchar(50) not null',
                ),
                'KEYS'    => array(
                  'cg_code' => 'UNIQUE(code)'
                ),
            )
        );

        BDb::run("
        INSERT INTO `{$tableCustomerGroup}` (`title`, `code`)
        VALUES('General', 'general'), ('NOT LOGGED IN', 'guest'), ('Retailer', 'retailer')
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
    } // end install

    public function upgrade_0_1_1()
    {
        $tableTierPrices = FCom_CustomerGroups_Model_TierPrice::table();

        $tableProduct = FCom_Catalog_Model_Product::table();
        $tableCustGroups = FCom_CustomerGroups_Model_Group::table();
        BDb::ddlTableDef($tableTierPrices,
            array(
                'COLUMNS' => array(
                    'id'         => 'int(10) unsigned not null auto_increment primary key',
                    'product_id' => 'int(10) unsigned not null',
                    'group_id'   => 'int(10) unsigned not null',
                    'base_price' => 'decimal(12,2) not null',
                    'sale_price' => 'decimal(12,2) not null',
                    'qty'        => 'int(10) unsigned not null default 1',
                ),
                'KEYS' => array(
                    'unq_prod_group_qty' => 'UNIQUE(product_id, group_id, qty)',
                ), // should we add unique key from product_id + group_id + qty ???
                'CONSTRAINTS' => array(
                    'fk_tier_product_id' => "FOREIGN KEY (product_id) REFERENCES {$tableProduct}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    'fk_tier_group_id' => "FOREIGN KEY (group_id) REFERENCES {$tableCustGroups}(id) ON DELETE CASCADE ON UPDATE CASCADE"
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
}