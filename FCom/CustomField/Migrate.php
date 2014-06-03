<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Migrate extends BClass
{
    public function install__0_2_0()
    {
        $tField = $this->FCom_CustomField_Model_Field->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tField} (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `field_type` enum('product') NOT NULL DEFAULT 'product',
              `field_code` varchar(50) NOT NULL,
              `field_name` varchar(50) NOT NULL,
              `table_field_type` varchar(20) NOT NULL,
              `admin_input_type` varchar(20) NOT NULL DEFAULT 'text',
              `frontend_label` text,
              `frontend_show` tinyint(1) NOT NULL DEFAULT '1',
              `config_json` text,
              `sort_order` int(11) NOT NULL DEFAULT '0',
              `facet_select` enum('No','Exclusive','Inclusive') NOT NULL DEFAULT 'No',
              `system` tinyint(1) NOT NULL DEFAULT '0',
              `multilanguage` tinyint(1) NOT NULL DEFAULT '0',
              `validation` varchar(100) DEFAULT NULL,
              `required` tinyint(1) NOT NULL DEFAULT '1',
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNQ_field_code` (`field_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tFieldOption = $this->FCom_CustomField_Model_FieldOption->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tFieldOption} (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `field_id` int(10) unsigned NOT NULL,
              `label` varchar(255) NOT NULL,
              `locale` varchar(10) NOT NULL DEFAULT '_',
              PRIMARY KEY (`id`),
              UNIQUE KEY `field_id__label` (`field_id`,`label`),
              CONSTRAINT `FK_{$tFieldOption}_field` FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tSet = $this->FCom_CustomField_Model_Set->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tSet} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `set_type` enum('product') NOT NULL DEFAULT 'product',
            `set_code` varchar(100) NOT NULL,
            `set_name` varchar(100) NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tSetField = $this->FCom_CustomField_Model_SetField->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tSetField} (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `set_id` int(10) unsigned NOT NULL,
              `field_id` int(10) unsigned NOT NULL,
              `position` smallint(5) unsigned DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNQ_set_id__field_id` (`set_id`,`field_id`),
              KEY `IDX_set_id__position` (`set_id`,`position`),
              KEY `FK_{$tSetField}_field` (`field_id`),
              CONSTRAINT `FK_{$tSetField}_field` FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `FK_{$tSetField}_set` FOREIGN KEY (`set_id`) REFERENCES {$tSet} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tProductField = $this->FCom_CustomField_Model_ProductField->table();
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tProductField} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` int(10) unsigned NOT NULL,
            `_fieldset_ids` text,
            `_add_field_ids` text,
            `_hide_field_ids` text,
            `_data_serialized` text,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tProductField}_product` FOREIGN KEY (`product_id`) REFERENCES {$tProduct} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tProdVariant = $this->FCom_CustomField_Model_ProductVariant->table();
        $this->BDb->ddlTableDef($tProdVariant, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'field_values' => 'varchar(255)',
                'variant_sku' => 'varchar(50)',
                'variant_price' => 'decimal(12,2)',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_product' => 'UNIQUE (product_id, field_values)',
                'IDX_sku' => '(variant_sku)',
            ],
        ]);
        $tField = $this->FCom_CustomField_Model_Field;
        while (true) {
            $dups = $tField->orm()->select('(min(id))', 'min_id')->group_by('field_code')
                ->having_gt('(count(*))', 1)->find_many_assoc('min_id');
            if (!$dups) {
                break;
            }
            $tField->delete_many(['id' => array_keys($dups)]);
        }

        $this->BDb->ddlTableDef($tField->table(), [
            'KEYS' => [
                'UNQ_field_code' => 'UNIQUE (field_code)',
            ],
        ]);

        $exist = $tField->orm()->where_in('field_code', ['color', 'size'])
            ->select('field_code')->find_many_assoc('field_code');
        if (empty($exist['color'])) {
            $tField->create([
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
            $tField->create([
                'field_code' => 'size',
                'field_name' => 'Size',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label' => 'Size',
                'frontend_show' => 0,
                'sort_order' => 1,
            ])->save();
        }

        $this->BDb->ddlTableDef($tProdVariant, ['COLUMNS' => ['variant_qty' => "init(11)" ]]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tField = $this->FCom_CustomField_Model_Field->table();
        $fieldName = 'frontend_show';
        if ($this->BDb->ddlFieldInfo($tField, $fieldName)) {
            return false;
        }
        $this->BDb->run(" ALTER TABLE {$tField} ADD {$fieldName} tinyint(1) not null default 1; ");
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tField = $this->FCom_CustomField_Model_Field->table();
        $this->BDb->ddlTableDef($tField, ['COLUMNS' => ['sort_order' => "int not null default '0'"]]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tField = $this->FCom_CustomField_Model_Field->table();
        $this->BDb->ddlTableDef($tField, ['COLUMNS' => ['facet_select' => "enum('No', 'Exclusive', 'Inclusive') NOT NULL DEFAULT 'No'"]]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $tField = $this->FCom_CustomField_Model_Field->table();
        $this->BDb->ddlTableDef($tField, ['COLUMNS' => ['system' => "tinyint(1) NOT NULL DEFAULT '0'"]]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $tProdField = $this->FCom_CustomField_Model_ProductField->table();
        $this->BDb->ddlTableDef($tProdField, ['COLUMNS' => ['_data_serialized' => "text null AFTER _hide_field_ids"]]);
    }

    public function upgrade__0_1_5__0_1_6()
    {
        $tProdVariant = $this->FCom_CustomField_Model_ProductVariant->table();
        $this->BDb->ddlTableDef($tProdVariant, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'product_id' => 'int unsigned not null',
                'field_values' => 'varchar(255)',
                'variant_sku' => 'varchar(50)',
                'variant_price' => 'decimal(12,2)',
                'data_serialized' => 'text',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_product' => 'UNIQUE (product_id, field_values)',
                'IDX_sku' => '(variant_sku)',
            ],
        ]);
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $tProdField = $this->FCom_CustomField_Model_Field->table();
        $this->BDb->ddlTableDef($tProdField, ['COLUMNS' => ['validation' => "varchar(100) null"]]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $tProdField = $this->FCom_CustomField_Model_Field->table();
        $this->BDb->ddlTableDef($tProdField, ['COLUMNS' => ['required' => "tinyint(1) NOT NULL DEFAULT '1'"]]);
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $fieldHlp = $this->FCom_CustomField_Model_Field;

        while (true) {
            $dups = $fieldHlp->orm()->select('(min(id))', 'min_id')->group_by('field_code')
                ->having_gt('(count(*))', 1)->find_many_assoc('min_id');
            if (!$dups) {
                break;
            }
            $fieldHlp->delete_many(['id' => array_keys($dups)]);
        }

        $this->BDb->ddlTableDef($fieldHlp->table(), [
            'KEYS' => [
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
        $tProdVariant = $this->FCom_CustomField_Model_ProductVariant->table();
        $this->BDb->ddlTableDef($tProdVariant, ['COLUMNS' => ['variant_qty' => "int(11)" ]]);
    }
}
