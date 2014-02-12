<?php

class FCom_Stock_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tSku = FCom_Stock_Model_Sku::table();
        $tBin = FCom_Stock_Model_Bin::table();

        BDb::ddlTableDef($tBin, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(50)',
                'description' => 'text',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
            ),
            'PRIMARY' => '(id)',
        ));

        BDb::ddlTableDef($tSku, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'sku' => 'varchar(128) not null',
                'bin_id' => 'int unsigned null',
                'qty_in_stock' => 'int not null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_sku' => 'UNIQUE (sku)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tSku}_bin" => "FOREIGN KEY (bin_id) REFERENCES {$tBin} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
    }
}
