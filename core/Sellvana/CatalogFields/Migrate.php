<?php

/**
 * Class Sellvana_CatalogFields_Migrate
 *
 * @property FCom_Core_Model_MediaLibrary                     $FCom_Core_Model_MediaLibrary
 * @property Sellvana_Catalog_Model_Product                   $Sellvana_Catalog_Model_Product
 * @property Sellvana_CatalogFields_Model_Field               $Sellvana_CatalogFields_Model_Field
 * @property Sellvana_CatalogFields_Model_FieldOption         $Sellvana_CatalogFields_Model_FieldOption
 * @property Sellvana_CatalogFields_Model_Set                 $Sellvana_CatalogFields_Model_Set
 * @property Sellvana_CatalogFields_Model_SetField            $Sellvana_CatalogFields_Model_SetField
 * @property Sellvana_CatalogFields_Model_ProductField        $Sellvana_CatalogFields_Model_ProductField
 * @property Sellvana_CatalogFields_Model_ProductVariant      $Sellvana_CatalogFields_Model_ProductVariant
 * @property Sellvana_CatalogFields_Model_ProductVarfield     $Sellvana_CatalogFields_Model_ProductVarfield
 * @property Sellvana_CatalogFields_Model_ProductVariantField $Sellvana_CatalogFields_Model_ProductVariantField
 * @property Sellvana_CatalogFields_Model_ProductVariantImage $Sellvana_CatalogFields_Model_ProductVariantImage
 * @property Sellvana_Catalog_Model_ProductPrice              $Sellvana_Catalog_Model_ProductPrice
 * @property Sellvana_CatalogFields_Model_ProductFieldData    $Sellvana_CatalogFields_Model_ProductFieldData
 * @property Sellvana_MultiSite_Model_Site                    $Sellvana_MultiSite_Model_Site
 * @property Sellvana_Catalog_Model_ProductMedia              $Sellvana_Catalog_Model_ProductMedia
 */
class Sellvana_CatalogFields_Migrate extends BClass
{
    public function install__0_5_1_0()
    {
        $hlpField = $this->Sellvana_CatalogFields_Model_Field;
        $tField = $hlpField->table();
        $tFieldOption = $this->Sellvana_CatalogFields_Model_FieldOption->table();
        $tSet = $this->Sellvana_CatalogFields_Model_Set->table();
        $tSetField = $this->Sellvana_CatalogFields_Model_SetField->table();
        $tProductField = $this->Sellvana_CatalogFields_Model_ProductField->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $tProdVariant = $this->Sellvana_CatalogFields_Model_ProductVariant->table();
        $tProdVarfield = $this->Sellvana_CatalogFields_Model_ProductVarfield->table();
        $tProdVariantField = $this->Sellvana_CatalogFields_Model_ProductVariantField->table();
        $tProdVariantImage = $this->Sellvana_CatalogFields_Model_ProductVariantImage->table();
        $tMediaFile = $this->FCom_Core_Model_MediaLibrary->table();
        $tPrice = $this->Sellvana_Catalog_Model_ProductPrice->table();

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

        $this->BDb->ddlTableDef($tSet, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'set_type' => "enum('product') not null default 'product'",
                'set_code' => 'varchar(100) not null',
                'set_name' => 'varchar(100) not null',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlTableDef($tSetField, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'set_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'position' => 'smallint(5) unsigned default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_set_id__field_id' => 'UNIQUE (set_id, field_id)',
                'IDX_set_id__position' => '(set_id, position)',
            ],
            BDb::CONSTRAINTS => [
                'field' => ['field_id', $tField],
                'set' => ['set_id', $tSet],
            ],
        ]);

        $this->BDb->ddlTableDef($tProductField, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                '_fieldset_ids' => 'text',
                '_add_field_ids' => 'text',
                '_hide_field_ids' => 'text',
                '_data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
            ],
        ]);

        $this->BDb->ddlTableDef($tProdVariant, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'field_values' => 'varchar(255)',
                'product_sku' => 'varchar(50)',
                'inventory_sku' => 'varchar(50)',
                'variant_price' => 'decimal(12,2)',
                //'variant_qty' => 'int(11)',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_product' => 'UNIQUE (product_id, field_values)',
                'IDX_product_sku' => '(product_sku)',
                'IDX_inventory_sku' => '(inventory_sku)',
            ],
        ]);

        $this->BDb->ddlTableDef($tProdVarfield, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'field_label' => 'varchar(50)',
                'position' => 'tinyint unsigned not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_product_field' => 'UNIQUE (product_id, field_id)',
                'IDX_product_position' => '(product_id, position)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'field'   => ['field_id', $tField],
            ],
        ]);

        $this->BDb->ddlTableDef($tProdVariantField, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'variant_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'varfield_id' => 'int unsigned not null',
                'option_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'product'  => ['product_id', $tProduct],
                'variant'  => ['variant_id', $tProdVariant],
                'field'    => ['field_id', $tField],
                'varfield' => ['varfield_id', $tProdVarfield],
                'option'   => ['option_id', $tFieldOption],
            ],
        ]);

        $this->BDb->ddlTableDef($tProdVariantImage, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'variant_id' => 'int unsigned not null',
                'file_id' => 'int unsigned not null',
                'product_media_id' => 'int unsigned not null',
                'position' => 'tinyint unsigned not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_variant_position' => '(variant_id, position)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'variant' => ['variant_id', $tProdVariant],
                'file'    => ['file_id', $tMediaFile],
            ],
        ]);

        $this->BDb->ddlTableDef($tPrice, [
            BDb::COLUMNS => [
                'variant_id' => 'INT UNSIGNED NULL DEFAULT NULL',
            ],
            BDb::CONSTRAINTS => [
                'variant' => ['variant_id', $tProdVariant],
            ],
        ]);

        while (true) {
            $dups = $hlpField->orm()->select('(min(id))', 'min_id')->group_by('field_code')
                ->having_gt('(count(*))', 1)->find_many_assoc('min_id');
            if (!$dups) {
                break;
            }
            $hlpField->delete_many(['id' => array_keys($dups)]);
        }

        $exist = $hlpField->orm()->where_in('field_code', ['color', 'size'])
            ->select('field_code')->find_many_assoc('field_code');
        if (empty($exist['color'])) {
            $hlpField->create([
                'field_code' => 'color',
                'field_name' => 'Color',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label' => 'Color',
                'frontend_show' => 0,
                'sort_order' => 1,
            ])->save();
        }
        if (empty($exist['size'])) {
            $hlpField->create([
                'field_code' => 'size',
                'field_name' => 'Size',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label' => 'Size',
                'frontend_show' => 0,
                'sort_order' => 1,
            ])->save();
        }
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tField = $this->Sellvana_CatalogFields_Model_Field->table();
        $fieldName = 'frontend_show';
        if ($this->BDb->ddlFieldInfo($tField, $fieldName)) {
            return false;
        }
        $this->BDb->run(" ALTER TABLE {$tField} ADD {$fieldName} tinyint(1) not null default 1; ");
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tField = $this->Sellvana_CatalogFields_Model_Field->table();
        $this->BDb->ddlTableDef($tField, [BDb::COLUMNS => ['sort_order' => "int not null default '0'"]]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tField = $this->Sellvana_CatalogFields_Model_Field->table();
        $this->BDb->ddlTableDef($tField, [BDb::COLUMNS => ['facet_select' => "enum('No', 'Exclusive', 'Inclusive') NOT NULL DEFAULT 'No'"]]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $tField = $this->Sellvana_CatalogFields_Model_Field->table();
        $this->BDb->ddlTableDef($tField, [BDb::COLUMNS => ['system' => "tinyint(1) NOT NULL DEFAULT '0'"]]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $tProdField = $this->Sellvana_CatalogFields_Model_ProductField->table();
        $this->BDb->ddlTableDef($tProdField, [BDb::COLUMNS => ['_data_serialized' => "text null AFTER _hide_field_ids"]]);
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $tProdVariant = $this->Sellvana_CatalogFields_Model_ProductVariant->table();
        $this->BDb->ddlTableDef($tProdVariant, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'field_values' => 'varchar(255)',
                'variant_sku' => 'varchar(50)',
                'variant_price' => 'decimal(12,2)',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_product' => 'UNIQUE (product_id, field_values)',
                'IDX_sku' => '(variant_sku)',
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $tProdField = $this->Sellvana_CatalogFields_Model_Field->table();
        $this->BDb->ddlTableDef($tProdField, [BDb::COLUMNS => ['validation' => "varchar(100) null"]]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tProdField = $this->Sellvana_CatalogFields_Model_Field->table();
        $this->BDb->ddlTableDef($tProdField, [BDb::COLUMNS => ['required' => "tinyint(1) NOT NULL DEFAULT '1'"]]);
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $fieldHlp = $this->Sellvana_CatalogFields_Model_Field;

        while (true) {
            $dups = $fieldHlp->orm()->select('(min(id))', 'min_id')->group_by('field_code')
                ->having_gt('(count(*))', 1)->find_many_assoc('min_id');
            if (!$dups) {
                break;
            }
            $fieldHlp->delete_many(['id' => array_keys($dups)]);
        }

        $this->BDb->ddlTableDef($fieldHlp->table(), [
            BDb::KEYS => [
                'UNQ_field_code' => 'UNIQUE (field_code)',
            ],
        ]);

        $exist = $fieldHlp->orm()->where_in('field_code', ['color', 'size'])
            ->select('field_code')->find_many_assoc('field_code');
        if (empty($exist['color'])) {
            $fieldHlp->create([
                'field_code' => 'color',
                'field_name' => 'Color',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label' => 'Color',
                'frontend_show' => 0,
                'sort_order' => 1,
            ])->save();
        }
        if (empty($exist['size'])) {
            $fieldHlp->create([
                'field_code' => 'size',
                'field_name' => 'Size',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label' => 'Size',
                'frontend_show' => 0,
                'sort_order' => 1,
            ])->save();
        }
    }

    public function upgrade__0_1_9__0_2_0()
    {
        $tProdVariant = $this->Sellvana_CatalogFields_Model_ProductVariant->table();
        $this->BDb->ddlTableDef($tProdVariant, [BDb::COLUMNS => ['variant_qty' => "int(11)" ]]);
    }

    public function upgrade__0_2_0__0_2_1()
    {
        $tProduct          = $this->Sellvana_Catalog_Model_Product->table();
        $tField            = $this->Sellvana_CatalogFields_Model_Field->table();
        $tFieldOption      = $this->Sellvana_CatalogFields_Model_FieldOption->table();
        $tProdVariant      = $this->Sellvana_CatalogFields_Model_ProductVariant->table();
        $tProdVarfield     = $this->Sellvana_CatalogFields_Model_ProductVarfield->table();
        $tProdVariantField = $this->Sellvana_CatalogFields_Model_ProductVariantField->table();
        $tProdVariantImage = $this->Sellvana_CatalogFields_Model_ProductVariantImage->table();
        $tMediaFile        = $this->FCom_Core_Model_MediaLibrary->table();

        $this->BDb->ddlTableDef($tProdVarfield, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'field_label' => 'varchar(50)',
                'position' => 'tinyint unsigned not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_product_field' => 'UNIQUE (product_id, field_id)',
                'IDX_product_position' => '(product_id, position)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'field'   => ['field_id', $tField],
            ],
        ]);

        $this->BDb->ddlTableDef($tProdVariantField, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'variant_id' => 'int unsigned not null',
                'field_id' => 'int unsigned not null',
                'varfield_id' => 'int unsigned not null',
                'option_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'product'  => ['product_id', $tProduct],
                'variant'  => ['variant_id', $tProdVariant],
                'field'    => ['field_id', $tField],
                'varfield' => ['varfield_id', $tProdVarfield],
                'option'   => ['option_id', $tFieldOption],
            ],
        ]);

        $this->BDb->ddlTableDef($tProdVariantImage, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'variant_id' => 'int unsigned not null',
                'file_id' => 'int unsigned not null',
                'product_media_id' => 'int unsigned not null',
                'position' => 'tinyint unsigned not null default 0',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_variant_position' => '(variant_id, position)',
            ],
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'variant' => ['variant_id', $tProdVariant],
                'file'    => ['file_id', $tMediaFile],
            ],
        ]);
    }

    public function upgrade__0_2_1__0_2_2()
    {
        $tFieldOption = $this->Sellvana_CatalogFields_Model_FieldOption->table();
        $this->BDb->ddlTableDef($tFieldOption, [
            BDb::COLUMNS => [
                'data_serialized' => 'text', // for translations
            ],
        ]);
    }

    public function upgrade__0_2_2__0_2_3()
    {
        $tProdVariant = $this->Sellvana_CatalogFields_Model_ProductVariant->table();

        if (!$this->BDb->ddlFieldInfo($tProdVariant, 'inventory_sku')) {
            $this->BDb->ddlTableDef($tProdVariant, [
                BDb::COLUMNS => [
                    'variant_sku' => 'RENAME inventory_sku varchar(50)',
                    'product_sku' => 'varchar(50)',
                ],
                BDb::KEYS => [
                    'IDX_sku' => BDb::DROP,
                    'IDX_inventory_sku' => '(inventory_sku)',
                    'IDX_product_sku' => '(product_sku)',
                ],
            ]);
            $this->BDb->run("UPDATE {$tProdVariant} SET product_sku=inventory_sku");
        }
    }

    /*
    public function upgrade__0_2_3__0_2_4()
    {
        $tProdVariant = $this->Sellvana_CatalogFields_Model_ProductVariant->table();
        $this->BDb->ddlTableDef($tProdVariant, [
            BDb::COLUMNS => [
                'variant_price' => 'DROP',
            ],
        ]);
    }
    */

    public function upgrade__0_2_4__0_2_5()
    {
        $tPrice = $this->Sellvana_Catalog_Model_ProductPrice->table();

        $tableProductVariant = $this->Sellvana_CatalogFields_Model_ProductVariant->table();
        $this->BDb->ddlTableDef($tPrice, [
            BDb::COLUMNS => [
                'variant_id' => 'INT UNSIGNED NULL DEFAULT NULL',
            ],
            BDb::CONSTRAINTS => [
                'variant' => ['variant_id', $tableProductVariant],
            ],
        ]);
    }

    public function upgrade__0_5_0_0__0_5_1_0()
    {
        $tField = $this->Sellvana_CatalogFields_Model_Field->table();
        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'data_serialized' => 'text',
                'create_at' => 'datetime default null',
                'update_at' => 'datetime default null',
            ],
        ]);
    }

    public function upgrade__0_5_1_0__0_5_5_0()
    {
        $tField = $this->Sellvana_CatalogFields_Model_Field->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();
        $tProductField = $this->Sellvana_CatalogFields_Model_ProductFieldData->table();
        $tFieldOption = $this->Sellvana_CatalogFields_Model_FieldOption->table();
        $tSet = $this->Sellvana_CatalogFields_Model_Set->table();

        $this->BDb->ddlTableDef($tProductField, [
            BDb::COLUMNS => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'product_id' => "int(10) UNSIGNED NOT NULL",
                'set_id' => "int(10) UNSIGNED DEFAULT NULL",
                'field_id' => "int(10) UNSIGNED NOT NULL",
                'value_id' => "int(10) UNSIGNED",
                'value_int' => "int",
                'value_dec' => "decimal(12,2)",
                'value_var' => "varchar(255)",
                'value_text' => "text",
                'value_date' => "datetime",
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'product' => ['product_id', $tProduct],
                'set' => ['set_id', $tSet],
                'field' => ['field_id', $tField],
                'value' => ['value_id', $tFieldOption],
            ],
        ]);

        $fields = $this->Sellvana_CatalogFields_Model_Field->orm('f')->find_many();
        $fieldsAssoc = [];
        foreach ($fields as $field) {
            $fieldsAssoc[$field->get('field_code')] = $field;
        }

        $options = $this->Sellvana_CatalogFields_Model_FieldOption->orm('fo')->find_many();
        $optionsAssoc = [];
        foreach ($options as $option) {
            if (empty($optionsAssoc[$option->get('field_id')])) {
                $optionsAssoc[$option->get('field_id')] = [];
            }

            $optionsAssoc[$option->get('field_id')][$option->get('label')] = $option->get('id');
        }

        $oldData = $this->Sellvana_CatalogFields_Model_ProductField->orm('pf')->find_many();
        foreach ($oldData as $row) {
            $productId = $row->get('product_id');

            foreach ($fieldsAssoc as $fieldCode => $field) {
                $value = $row->get($fieldCode);
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
                }

                if ($fieldInputType == 'select') {
                    if (!empty($optionsAssoc[$field->get('id')][$value])) {
                        $value = $optionsAssoc[$field->get('id')][$value];
                        $valueColumn = 'value_id';
                    }
                }

                $this->Sellvana_CatalogFields_Model_ProductFieldData->create([
                    'product_id' => $productId,
                    'field_id' => $field->get('id'),
                    $valueColumn => $value
                ])->save();
            }
        }

        $fields = $this->Sellvana_CatalogFields_Model_Field->orm('f')->find_many();
        foreach ($fields as $field) {
            $fieldDbType = [];
            preg_match('/[a-zA-Z]+/', $field->get('table_field_type'), $fieldDbType);
            if (count($fieldDbType)) {
                $field->set('table_field_type', $fieldDbType[0])->save();
            }
        }

        $tProductField = $this->Sellvana_CatalogFields_Model_ProductFieldData->table();
        $this->BDb->ddlTableDef($tProductField, [BDb::COLUMNS => ['position' => "tinyint(3) NOT NULL DEFAULT '0' after `field_id`"]]);
    }

    public function upgrade__0_5_5_0__0_5_6_0()
    {
        $fHlp = $this->Sellvana_CatalogFields_Model_Field;

        if (($field = $fHlp->getField('size'))) {
            $field->set('table_field_type', 'options')->save();
        }
        if (($field = $fHlp->getField('color'))) {
            $field->set('table_field_type', 'options')->save();
        }

        $foHlp = $this->Sellvana_CatalogFields_Model_FieldOption;

        $options = $foHlp->preloadAllFieldsOptions()->getAllFieldsOptions();
        $optionsByLabel = [];
        foreach ($options as $fieldId => $fieldOptions) {
            foreach ($fieldOptions as $optionId => $option) {
                $optionsByLabel[$fieldId][strtolower($option->get('label'))] = $option->id();
            }
        }

        $orm = $this->Sellvana_CatalogFields_Model_ProductFieldData->orm('pfd')
            ->join($fHlp->table(), ['f.id', '=', 'pfd.field_id'], 'f')
            ->where('f.table_field_type', 'options')
            ->select('pfd.*');

        $orm->iterate(function($row) use (&$optionsByLabel, $fHlp, $foHlp) {
            $fId = $row->get('field_id');
            $label = $row->get('value_var');
            $valueLower = strtolower($label);
            if (!$valueLower) {
                return;
            }
            if (!empty($optionsByLabel[$fId][$valueLower])) {
                $valueId = $optionsByLabel[$fId][$valueLower];
            } else {
                $valueId = $foHlp->create(['field_id' => $fId, 'label' => $label])->save()->id();
                $optionsByLabel[$fId][$valueLower] = $valueId;
            }
            $row->set(['value_var' => null, 'value_id' => $valueId])->save();
        });
    }

    public function upgrade__0_5_6_0__0_5_7_0()
    {
        $fHlp = $this->Sellvana_CatalogFields_Model_Field;
        $fields = $fHlp->orm('f')
            ->where_in('admin_input_type', ['select', 'multiselect'])
            ->where_not_equal('table_field_type', 'options')
            ->find_many();

        foreach ($fields as $field) {
            $field->set('table_field_type', 'options')->save();
        }
    }

    public function after__Sellvana_MultiSite__0_5_2_0()
    {
        $tProductField = $this->Sellvana_CatalogFields_Model_ProductFieldData->table();
        $tSite = $this->Sellvana_MultiSite_Model_Site->table();
        $this->BDb->ddlTableDef($tProductField, [
            BDb::COLUMNS => [
                'locale' => "varchar(10) DEFAULT NULL after `position`",
                'site_id' => "int(10) UNSIGNED DEFAULT NULL after `position`",
            ],
            BDb::CONSTRAINTS => [
                'site' => ['site_id', $tSite],
            ],
        ]);
    }
    public function upgrade__0_5_7_0__0_5_8_0()
    {
        if ($this->BMigrate->isModuleVersion('Sellvana_MultiSite', '0.5.2.0~')) {
            $this->after__Sellvana_MultiSite__0_5_2_0();
        }
    }

    public function upgrade__0_5_8_0__0_5_9_0()
    {
        $set = $this->Sellvana_CatalogFields_Model_Set;
        $set->create([
            'set_type' => 'product',
            'set_name' => 'Default',
            'set_code' => 'default',
        ])->save();
    }

    public function upgrade__0_6_0_0__0_6_1_0()
    {
        $tProductVariantImage = $this->Sellvana_CatalogFields_Model_ProductVariantImage->table();
        $tProductMedia = $this->Sellvana_Catalog_Model_ProductMedia->table();

        $this->BDb->ddlTableDef($tProductVariantImage, [
            BDb::CONSTRAINTS => [
                'product_media' => ['product_media_id', $tProductMedia],
            ],
        ]);
    }

    public function upgrade__0_6_1_0__0_6_2_0()
    {
        $tField = $this->Sellvana_CatalogFields_Model_Field->table();
        $tFieldOption = $this->Sellvana_CatalogFields_Model_FieldOption->table();

        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'swatch_type' => "char(1) not null default 'N'",
            ],
        ]);

        $this->BDb->ddlTableDef($tFieldOption, [
            BDb::COLUMNS => [
                'swatch_info' => 'text default null',
            ],
        ]);

        $this->Sellvana_CatalogFields_Model_Field->getField('color')->set('swatch_type', 'C')->save();
    }
}
