<?php

/**
 * Class Sellvana_CustomerFields_Migrate
 *
 * @property FCom_Core_Model_MediaLibrary                    $FCom_Core_Model_MediaLibrary
 * @property Sellvana_Catalog_Model_Product                  $Sellvana_Catalog_Model_Product
 * @property FCom_Core_Model_Field             $FCom_Core_Model_Field
 * @property FCom_Core_Model_Fieldset       FCom_Core_Model_Fieldset
 * @property FCom_Core_Model_FieldOption       $FCom_Core_Model_FieldOption
 * @property Sellvana_CustomerFields_Model_CustomerFieldData $Sellvana_CustomerFields_Model_CustomerFieldData
 * @property Sellvana_Customer_Model_Customer                $Sellvana_Customer_Model_Customer
 * @property Sellvana_MultiSite_Model_Site                   $Sellvana_MultiSite_Model_Site
 */
class Sellvana_CustomerFields_Migrate extends BClass
{
    public function install__0_6_0_0()
    {
        $tField             = $this->FCom_Core_Model_Field->table();
        $tFieldSet          = $this->FCom_Core_Model_Fieldset->table();
        $tCustomerFieldData = $this->Sellvana_CustomerFields_Model_CustomerFieldData->table();
        $tCustomer          = $this->Sellvana_Customer_Model_Customer->table();
        $tFieldOption       = $this->FCom_Core_Model_FieldOption->table();

        $this->BDb->ddlTableDef($tCustomerFieldData, [
            BDb::COLUMNS     => [
                'id'          => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'customer_id' => "int(10) UNSIGNED NOT NULL",
                'field_id'    => "int(10) UNSIGNED NOT NULL",
                'position'    => "tinyint(3) NOT NULL DEFAULT '0'",
                'locale'      => "varchar(10) DEFAULT NULL",
                'value_id'    => "int(10) UNSIGNED",
                'value_int'   => "int",
                'value_dec'   => "decimal(12,2)",
                'value_var'   => "varchar(255)",
                'value_text'  => "text",
                'value_date'  => "datetime",
            ],
            BDb::PRIMARY     => '(id)',
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
                'field'    => ['field_id', $tField],
                'value'    => ['value_id', $tFieldOption],
            ],
        ]);
    }

    public function upgrade__0_6_0_0__0_6_1_0()
    {
        $tField = $this->FCom_Core_Model_Field->table();
        $tFieldSet          = $this->FCom_Core_Model_Fieldset->table();
        $tCustomerFieldData = $this->Sellvana_CustomerFields_Model_CustomerFieldData->table();

        // update field type to have customer option
        //TODO: refactor to use data_serialized
        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'register_form' => "BOOLEAN DEFAULT 0",
                'account_edit'  => "BOOLEAN DEFAULT 0",
            ],
        ]);

        $this->BDb->ddlTableDef($tCustomerFieldData, [
            BDb::COLUMNS => [
                'set_id' => 'int unsigned null',
            ],
            BDb::CONSTRAINTS => [
                'set' => ['set_id', $tFieldSet, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);
    }
}
