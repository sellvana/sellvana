<?php

class FCom_MultiLanguage_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tTrans = FCom_MultiLanguage_Model_Translation::table();
        $tIndexField = FCom_CatalogIndex_Model_Field::table();
        $tCustomField = FCom_CustomField_Model_Field::table();
        $tCustomFieldOption = FCom_CustomField_Model_FieldOption::table();
        BDb::ddlTableDef($tTrans, array(
            'COLUMNS' => array(
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'entity_type' => 'VARCHAR(30) NOT NULL',
                'entity_id' => 'INT UNSIGNED NOT NULL',
                'locale' => 'VARCHAR(10) NOT NULL',
                'data_serialized' => 'MEDIUMTEXT NOT NULL',
                'field' => 'varchar(50) NOT NULL',
                'value' => 'text NOT NULL'
            ),
            'PRIMARY' => '(`id`)',
            'KEYS' => array(
                'UNQ_type_id_locale_field' => 'UNIQUE (`entity_type`, `entity_id`, `locale`, `field`)'
            ),
        ));

        BDb::ddlTableDef($tIndexField, array(
            'COLUMNS' => array(
                'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
                'multilanguage' => 'bool NOT NULL default 0'
            ),
        ));

        BDb::ddlTableDef($tCustomFieldOption, array(
            'COLUMNS' => array(
                'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
            ),
        ));
        BDb::ddlTableDef($tCustomField, array(
            'COLUMNS' => array(
                'multilanguage' => 'bool NOT NULL default 0'
            ),
        ));
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tIndexField = FCom_CatalogIndex_Model_Field::table();
        $tCustField = FCom_CustomField_Model_Field::table();
        $tCustFieldOption = FCom_CustomField_Model_FieldOption::table();
        $tTrans = FCom_MultiLanguage_Model_Translation::table();

        BDb::ddlTableDef($tTrans, array(
            'COLUMNS' => array(
               'field' => 'varchar(50) NOT NULL',
               'value' => 'text NOT NULL'
            ),
            'KEYS' => array(
                'UNQ_id' => 'DROP',
                'UNQ_type_id_locale_field' => 'UNIQUE (`entity_type`, `entity_id`, `locale`, `field`)'
            ),
        ));

        BDb::ddlTableDef($tIndexField, array(
            'COLUMNS' => array(
               'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
               'multilanguage' => 'bool NOT NULL default 0'
            ),
        ));

        BDb::ddlTableDef($tCustFieldOption, array(
            'COLUMNS' => array(
               'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
            ),
        ));
        BDb::ddlTableDef($tCustField, array(
            'COLUMNS' => array(
               'multilanguage' => 'bool NOT NULL default 0'
            ),
        ));

        //todo
        /*
         check session locale, load
         find_many . '::find_many:after' frontend

         */
    }
}
