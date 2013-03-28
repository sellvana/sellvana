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
        $tableCustomer = FCom_CustomerGroups_Model_Group::table();

        BDb::ddlTableDef($tableCustomer,
                         array(
                              'COLUMNS' => array(
                                  'id'    => 'int(10) unsigned primary key auto_increment',
                                  'title' => 'varchar(100) not null',
                                  'code'  => 'varchar(50) not null',
                              ),
                              'KEYS'    => array(
                                  'cg_code' => 'UNIQUE(code)'
                              ),
                         ));
    }
}