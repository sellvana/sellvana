<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_MultiLanguage_Migrate
 *
 * @property FCom_CatalogIndex_Model_Field $FCom_CatalogIndex_Model_Field
 * @property FCom_CustomField_Model_Field $FCom_CustomField_Model_Field
 * @property FCom_CustomField_Model_FieldOption $FCom_CustomField_Model_FieldOption
 * @property FCom_MultiLanguage_Model_Translation $FCom_MultiLanguage_Model_Translation
 */

class FCom_MultiLanguage_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tTrans = $this->FCom_MultiLanguage_Model_Translation->table();
        $tIndexField = $this->FCom_CatalogIndex_Model_Field->table();
        $tCustomField = $this->FCom_CustomField_Model_Field->table();
        $tCustomFieldOption = $this->FCom_CustomField_Model_FieldOption->table();
        $this->BDb->ddlTableDef($tTrans, [
            BDb::COLUMNS => [
                'id' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
                'entity_type' => 'VARCHAR(30) NOT NULL',
                'entity_id' => 'INT UNSIGNED NOT NULL',
                'locale' => 'VARCHAR(10) NOT NULL',
                'data_serialized' => 'MEDIUMTEXT NOT NULL',
                'field' => 'varchar(50) NOT NULL',
                'value' => 'text NOT NULL'
            ],
            BDb::PRIMARY => '(`id`)',
            BDb::KEYS => [
                'UNQ_type_id_locale_field' => 'UNIQUE (`entity_type`, `entity_id`, `locale`, `field`)'
            ],
        ]);

        $this->BDb->ddlTableDef($tIndexField, [
            BDb::COLUMNS => [
                'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
                'multilanguage' => 'bool NOT NULL default 0'
            ],
        ]);

        $this->BDb->ddlTableDef($tCustomFieldOption, [
            BDb::COLUMNS => [
                'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
            ],
        ]);
        $this->BDb->ddlTableDef($tCustomField, [
            BDb::COLUMNS => [
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
            BDb::COLUMNS => [
               'field' => 'varchar(50) NOT NULL',
               'value' => 'text NOT NULL'
            ],
            BDb::KEYS => [
                'UNQ_id' => BDb::DROP,
                'UNQ_type_id_locale_field' => 'UNIQUE (`entity_type`, `entity_id`, `locale`, `field`)'
            ],
        ]);

        $this->BDb->ddlTableDef($tIndexField, [
            BDb::COLUMNS => [
               'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
               'multilanguage' => 'bool NOT NULL default 0'
            ],
        ]);

        $this->BDb->ddlTableDef($tCustFieldOption, [
            BDb::COLUMNS => [
               'locale' => 'VARCHAR(10) NOT NULL DEFAULT "_"',
            ],
        ]);
        $this->BDb->ddlTableDef($tCustField, [
            BDb::COLUMNS => [
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
