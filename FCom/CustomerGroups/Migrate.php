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
    }
}