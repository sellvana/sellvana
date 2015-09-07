<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerFields_Migrate
 *
 * @property FCom_Core_Model_MediaLibrary                    $FCom_Core_Model_MediaLibrary
 * @property Sellvana_Catalog_Model_Product                  $Sellvana_Catalog_Model_Product
 * @property Sellvana_CustomerFields_Model_Field             $Sellvana_CustomerFields_Model_Field
 * @property Sellvana_CustomerFields_Model_FieldOption       $Sellvana_CustomerFields_Model_FieldOption
 * @property Sellvana_CustomerFields_Model_CustomerFieldData $Sellvana_CustomerFields_Model_CustomerField
 * @property Sellvana_Customer_Model_Customer                $Sellvana_Customer_Model_Customer
 */
class Sellvana_CustomerFields_Migrate extends BClass
{
    public function install__0_5_0_0()
    {
        $tField = $this->Sellvana_CustomerFields_Model_Field->table();
        $tFieldOption = $this->Sellvana_CustomerFields_Model_FieldOption->table();
        $tCustomerField = $this->Sellvana_CustomerFields_Model_CustomerField->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();

        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'field_type' => "enum('product') NOT NULL DEFAULT 'product'",
                'field_code' => "varchar(50) NOT NULL",
                'field_name' => "varchar(50) NOT NULL",
                'table_field_type' => "varchar(20) NOT NULL",
                'admin_input_type' => "varchar(20) NOT NULL DEFAULT 'text'",
                'frontend_label' => "text",
                'frontend_show' => "tinyint(1) NOT NULL DEFAULT '1'",
                'config_json' => "text",
                'sort_order' => "int(11) NOT NULL DEFAULT '0'",
                'facet_select' => "enum('No','Exclusive','Inclusive') NOT NULL DEFAULT 'No'",
                'system' => "tinyint(1) NOT NULL DEFAULT '0'",
                'multilanguage' => "tinyint(1) NOT NULL DEFAULT '0'",
                'validation' => "varchar(100) DEFAULT NULL",
                'required' => "tinyint(1) NOT NULL DEFAULT '1'",
                'data_serialized' => 'text',
                'create_at' => 'datetime default null',
                'update_at' => 'datetime default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_field_code' => 'UNIQUE (field_code)',
            ],
        ]);

        $this->BDb->ddlTableDef($tFieldOption, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'field_id' => 'int unsigned not null',
                'label' => 'varchar(255) not null',
                'locale' => "varchar(10) not null default '_'",
                'data_serialized' => 'text', // for translations
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'field_id__label' => 'UNIQUE (field_id, label)',
            ],
            BDb::CONSTRAINTS => [
                'field' => ['field_id', $tField],
            ],
        ]);

        $this->BDb->ddlTableDef($tCustomerField, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_id' => 'int unsigned not null',
                '_fieldset_ids' => 'text',
                '_add_field_ids' => 'text',
                '_hide_field_ids' => 'text',
                '_data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
            ],
        ]);

    }

    public function upgrade__0_5_0_0__0_5_1_0()
    {
        $tField = $this->Sellvana_CustomerFields_Model_Field->table();

        // update field type to have customer option
        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'field_type' => "enum('customer', 'product') NOT NULL DEFAULT 'customer'",
            ]
        ]);

        $modelsForUpdate = $this->Sellvana_CustomerFields_Model_Field->orm()->where('field_type', 'product')
                                                                     ->find_many();
        // update any old records to be customer filed type
        if($modelsForUpdate){
            foreach ($modelsForUpdate as $m) {
                $m->set('field_type', 'customer')->save();
            }
        }

        // remove product field_type
        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'field_type' => "enum('customer') NOT NULL DEFAULT 'customer'",
            ]
        ]);

        $tCustomerField = $this->Sellvana_CustomerFields_Model_CustomerField->table();
        $this->BDb->run(sprintf('RENAME TABLE `%s` TO `%s`', $this->BDb->t('fcom_customer_custom'), $tCustomerField));
        $this->BDb->ddlTableDef($tCustomerField, [
            BDb::COLUMNS     => [
                '_fieldset_ids'   => 'DROP',
                '_add_field_ids'  => 'DROP',
                '_hide_field_ids' => 'DROP',
            ],
        ]);
    }

    public function upgrade__0_5_1_0__0_5_1_1()
    {
        $tField = $this->Sellvana_CustomerFields_Model_Field->table();

        // update field type to have customer option
        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'register_form' => "BOOLEAN DEFAULT 0",
                'account_edit' => "BOOLEAN DEFAULT 0",
            ]
        ]);
    }
}
