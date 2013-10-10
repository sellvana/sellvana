<?php

class FCom_MultiLanguage_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tTrans = FCom_MultiLanguage_Model_Translation::table();
        BDb::ddlTableDef($tTrans, array(
            'COLUMNS' => array(
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'entity_type' => 'VARCHAR(30) NOT NULL',
                'entity_id' => 'INT UNSIGNED NOT NULL',
                'locale' => 'VARCHAR(10) NOT NULL',
                'data_serialized' => 'MEDIUMTEXT NOT NULL',
            ),
            'PRIMARY' => '(`id`)',
            'KEYS' => array(
                'UNQ_id' => 'UNIQUE (`entity_type`, `entity_id`, `locale`)',
            ),
        ));
    }
}
