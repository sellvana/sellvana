<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_MultiLanguage_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tTrans = $this->FCom_MultiLanguage_Model_Translation->table();
        $tIndexField = $this->FCom_CatalogIndex_Model_Field->table();
        $tCustomField = $this->FCom_CustomField_Model_Field->table();
        $tCustomFieldOption = $this->FCom_CustomField_Model_FieldOption->table();
        $this->BDb->ddlTableDef($tTrans, [
            'COLUMNS' => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'entity_type' => 'VARCHAR(30) NOT NULL',
                'entity_id' => 'INT UNSIGNED NOT NULL',
                'locale' => 'VARCHAR(10) NOT NULL',
                'data_serialized' => 'MEDIUMTEXT NOT NULL',
                'field' => 'varchar(50) NOT NULL',
                'value' => 'text NOT NULL'
            ],
            'PRIMARY' => '(`id`)',
            'KEYS' => [
                'UNQ_type_id_locale_field' => 'UNIQUE (`entity_type`, `entity_id`, `locale`, `field`)'
            ],
        ]);

        $this->BDb->ddlTableDef($tIndexField, [
            'COLUMNS' => [
                'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
                'multilanguage' => 'bool NOT NULL default 0'
            ],
        ]);

        $this->BDb->ddlTableDef($tCustomFieldOption, [
            'COLUMNS' => [
                'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
            ],
        ]);
        $this->BDb->ddlTableDef($tCustomField, [
            'COLUMNS' => [
                'multilanguage' => 'bool NOT NULL default 0'
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tIndexField = $this->FCom_CatalogIndex_Model_Field->table();
        $tCustField = $this->FCom_CustomField_Model_Field->table();
        $tCustFieldOption = $this->FCom_CustomField_Model_FieldOption->table();
        $tTrans = $this->FCom_MultiLanguage_Model_Translation->table();

        $this->BDb->ddlTableDef($tTrans, [
            'COLUMNS' => [
               'field' => 'varchar(50) NOT NULL',
               'value' => 'text NOT NULL'
            ],
            'KEYS' => [
                'UNQ_id' => 'DROP',
                'UNQ_type_id_locale_field' => 'UNIQUE (`entity_type`, `entity_id`, `locale`, `field`)'
            ],
        ]);

        $this->BDb->ddlTableDef($tIndexField, [
            'COLUMNS' => [
               'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
               'multilanguage' => 'bool NOT NULL default 0'
            ],
        ]);

        $this->BDb->ddlTableDef($tCustFieldOption, [
            'COLUMNS' => [
               'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
            ],
        ]);
        $this->BDb->ddlTableDef($tCustField, [
            'COLUMNS' => [
               'multilanguage' => 'bool NOT NULL default 0'
            ],
        ]);

        //todo
        /*
         check session locale, load
         find_many . '::find_many:after' frontend

         */
    }
}
