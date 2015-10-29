<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerFields_Migrate
 *
 * @property FCom_Core_Model_MediaLibrary                    $FCom_Core_Model_MediaLibrary
 * @property Sellvana_Catalog_Model_Product                  $Sellvana_Catalog_Model_Product
 * @property Sellvana_CustomerFields_Model_Field             $Sellvana_CustomerFields_Model_Field
 * @property Sellvana_CustomerFields_Model_FieldOption       $Sellvana_CustomerFields_Model_FieldOption
 * @property Sellvana_CustomerFields_Model_CustomerFieldData $Sellvana_CustomerFields_Model_CustomerFieldData
 * @property Sellvana_Customer_Model_Customer                $Sellvana_Customer_Model_Customer
 * @property Sellvana_MultiSite_Model_Site                   $Sellvana_MultiSite_Model_Site
 */
class Sellvana_CustomerFields_Migrate extends BClass
{
    public function install__0_5_0_0()
    {
        $tField         = $this->Sellvana_CustomerFields_Model_Field->table();
        $tFieldOption   = $this->Sellvana_CustomerFields_Model_FieldOption->table();
        $tCustomerField = $this->Sellvana_CustomerFields_Model_CustomerFieldData->table();
        $tCustomer      = $this->Sellvana_Customer_Model_Customer->table();

        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'id'               => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'field_type'       => "enum('product') NOT NULL DEFAULT 'product'",
                'field_code'       => "varchar(50) NOT NULL",
                'field_name'       => "varchar(50) NOT NULL",
                'table_field_type' => "varchar(20) NOT NULL",
                'admin_input_type' => "varchar(20) NOT NULL DEFAULT 'text'",
                'frontend_label'   => "text",
                'frontend_show'    => "tinyint(1) NOT NULL DEFAULT '1'",
                'config_json'      => "text",
                'sort_order'       => "int(11) NOT NULL DEFAULT '0'",
                'facet_select'     => "enum('No','Exclusive','Inclusive') NOT NULL DEFAULT 'No'",
                'system'           => "tinyint(1) NOT NULL DEFAULT '0'",
                'multilanguage'    => "tinyint(1) NOT NULL DEFAULT '0'",
                'validation'       => "varchar(100) DEFAULT NULL",
                'required'         => "tinyint(1) NOT NULL DEFAULT '1'",
                'data_serialized'  => 'text',
                'create_at'        => 'datetime default null',
                'update_at'        => 'datetime default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS    => [
                'UNQ_field_code' => 'UNIQUE (field_code)',
            ],
        ]);

        $this->BDb->ddlTableDef($tFieldOption, [
            BDb::COLUMNS     => [
                'id'              => 'int unsigned not null auto_increment',
                'field_id'        => 'int unsigned not null',
                'label'           => 'varchar(255) not null',
                'locale'          => "varchar(10) not null default '_'",
                'data_serialized' => 'text', // for translations
            ],
            BDb::PRIMARY     => '(id)',
            BDb::KEYS        => [
                'field_id__label' => 'UNIQUE (field_id, label)',
            ],
            BDb::CONSTRAINTS => [
                'field' => ['field_id', $tField],
            ],
        ]);

        $this->BDb->ddlTableDef($tCustomerField, [
            BDb::COLUMNS     => [
                'id'               => 'int unsigned not null auto_increment',
                'customer_id'      => 'int unsigned not null',
                '_fieldset_ids'    => 'text',
                '_add_field_ids'   => 'text',
                '_hide_field_ids'  => 'text',
                '_data_serialized' => 'text',
            ],
            BDb::PRIMARY     => '(id)',
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
        if ($modelsForUpdate) {
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

        $tCustomerField = $this->Sellvana_CustomerFields_Model_CustomerFieldData->table();
        //$this->BDb->run(sprintf('RENAME TABLE `%s` TO `%s`', $this->BDb->t('fcom_customer_custom'), $tCustomerField));
        $this->BDb->ddlTableDef($tCustomerField, [
            BDb::COLUMNS => [
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
                'account_edit'  => "BOOLEAN DEFAULT 0",
            ]
        ]);
    }

    public function upgrade__0_5_1_1__0_5_2_0()
    {
        $tField         = $this->Sellvana_CustomerFields_Model_Field->table();
        $tCustomer      = $this->Sellvana_Customer_Model_Customer->table();
        $tCustomerField = $this->Sellvana_CustomerFields_Model_CustomerFieldData->table();
        $tFieldOption   = $this->Sellvana_CustomerFields_Model_FieldOption->table();
        $tSite          = $this->Sellvana_MultiSite_Model_Site->table();

        $fieldsAssoc  = $this->Sellvana_CustomerFields_Model_Field->getAllFields();
        $optionsAssoc = $this->Sellvana_CustomerFields_Model_FieldOption->getAllFieldsOptions();

        $oldData = $this->Sellvana_CustomerFields_Model_CustomerFieldData->orm('cf')->find_many();
        $newData = [];

        /** @var Sellvana_CustomerFields_Model_CustomerFieldData $row */
        foreach ($oldData as $row) {
            $customerId = $row->get('customer_id');
            /** @var Sellvana_CustomerFields_Model_Field $field */
            foreach ($fieldsAssoc as $fieldCode => $field) {
                $value          = $row->get($fieldCode);
                $fieldInputType = $field->get('admin_input_type');
                if (is_null($value)) {
                    continue;
                }

                preg_match('/[a-zA-Z]+/', $field->get('table_field_type'), $fieldDbType);
                $valueColumn = 'value_var';
                if (count($fieldDbType)) {
                    switch ($fieldDbType[0]) {
                        case 'int':
                            $valueColumn = 'value_int';
                            break;
                        case 'text':
                            $valueColumn = 'value_text';
                            break;
                        case 'decimal':
                            $valueColumn = 'value_dec';
                            break;
                        case 'datetime':
                            $valueColumn = 'value_date';
                            break;
                        default:
                            $valueColumn = 'value_var';
                            break;
                    }
                    $field->set('table_field_type', $fieldDbType[0])->save(); // strip (255) type of suffixes
                }

                $fId = $field->id();
                if ($fieldInputType == 'select') {
                    if (!empty($optionsAssoc[$fId][$value])) {
                        $value       = $optionsAssoc[$fId][$value];
                        $valueColumn = 'value_id';
                    }
                }

                $newData[] = [
                    'customer_id' => $customerId,
                    'field_id'    => $fId,
                    $valueColumn  => $value
                ];
            }
        }

        $this->BDb->ddlDropTable($tCustomerField);

        $this->BDb->ddlTableDef($tCustomerField, [
            BDb::COLUMNS     => [
                'id'          => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'customer_id' => "int(10) UNSIGNED NOT NULL",
                'field_id'    => "int(10) UNSIGNED NOT NULL",
                'position'    => "tinyint(3) NOT NULL DEFAULT '0'",
                'locale'      => "varchar(10) DEFAULT NULL",
                'site_id'     => "int(10) UNSIGNED DEFAULT NULL",
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
                'site'     => ['site_id', $tSite],
            ],
        ]);

        foreach ($newData as $nd) {
            $this->Sellvana_CustomerFields_Model_CustomerFieldData->create($nd)->save();
        }

        $fHlp  = $this->Sellvana_CustomerFields_Model_Field;
        $foHlp = $this->Sellvana_CustomerFields_Model_FieldOption;

        $fields = $fHlp->orm('f')
                       ->where_in('admin_input_type', ['select', 'multiselect'])
                       ->where_not_equal('table_field_type', 'options')
                       ->find_many();

        foreach ($fields as $field) {
            $field->set('table_field_type', 'options')->save();
        }

        $options        = $foHlp->preloadAllFieldsOptions()->getAllFieldsOptions();
        $optionsByLabel = [];

        foreach ($options as $fieldId => $fieldOptions) {
            /**
             * @var Sellvana_CustomerFields_Model_FieldOption $option
             */
            foreach ($fieldOptions as $optionId => $option) {
                $optionsByLabel[$fieldId][strtolower($option->get('label'))] = $option->id();
            }
        }

        $orm = $this->Sellvana_CustomerFields_Model_CustomerFieldData->orm('cfd')
                                                                     ->join($fHlp->table(),
                                                                         ['f.id', '=', 'cfd.field_id'], 'f')
                                                                     ->where('f.table_field_type', 'options')
                                                                     ->select('cfd.*');

        $orm->iterate(function (Sellvana_CustomerFields_Model_CustomerFieldData $row) use (
            &$optionsByLabel, $fHlp, $foHlp
        ) {
            /** Sellvana_CustomerFields_Model_CustomerFieldData $row */
            $fId        = $row->get('field_id');
            $label      = $row->get('value_var');
            $valueLower = strtolower($label);
            if (!$valueLower) {
                return;
            }

            if (!empty($optionsByLabel[$fId][$valueLower])) {
                $valueId = $optionsByLabel[$fId][$valueLower];
            } else {
                $valueId                           = $foHlp->create(['field_id' => $fId, 'label' => $label])->save()
                                                           ->id();
                $optionsByLabel[$fId][$valueLower] = $valueId;
            }
            $row->set(['value_var' => null, 'value_id' => $valueId])->save();
        });
    }

}
