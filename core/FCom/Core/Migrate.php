<?php

/**
 * Class FCom_Core_Migrate
 *
 * @property FCom_Core_Model_ImportExport_Id $FCom_Core_Model_ImportExport_Id
 * @property FCom_Core_Model_ImportExport_Model $FCom_Core_Model_ImportExport_Model
 * @property FCom_Core_Model_ImportExport_Site $FCom_Core_Model_ImportExport_Site
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property FCom_Core_Model_Seq $FCom_Core_Model_Seq
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 * @property FCom_Core_Model_ExternalConfig $FCom_Core_Model_ExternalConfig
 * @property FCom_Core_Model_Field FCom_Core_Model_Field
 * @property FCom_Core_Model_FieldOption FCom_Core_Model_FieldOption
 * @property FCom_Core_Model_Fieldset FCom_Core_Model_Fieldset
 * @property FCom_Core_Model_FieldsetField FCom_Core_Model_FieldsetField
 */

class FCom_Core_Migrate extends BClass
{
    public function install__0_6_0_0()
    {
        $tMediaLibrary = $this->FCom_Core_Model_MediaLibrary->table();
        $this->BDb->ddlTableDef($tMediaLibrary, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'folder' => 'varchar(255) not null',
                'subfolder' => 'varchar(32) default null',
                'file_name' => 'varchar(255) not null',
                'file_size' => 'int(11) default null',
                'data_serialized' => 'text',
                'create_at' => 'datetime default null',
                'update_at' => 'datetime default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'field_file' => '(folder, file_name)',
                'IDX_create_at' => '(create_at)',
            ],
        ]);
        $this->BDb->ddlTableDef($this->FCom_Core_Model_Seq->table(), [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'current_seq_id' => 'varchar(15) not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_entity_type' => 'UNIQUE (entity_type)',
            ],
        ]);
        $this->BConfig->set('cookie/session_check_ip', 1, false, true);
        $this->BConfig->writeConfigFiles();
        $this->BDb->ddlTableDef($this->FCom_Core_Model_ImportExport_Id->table(), [
            BDb::COLUMNS => [
                'id'        => 'int(11)',
                'store_id'  => 'char(32)',
                'model'     => 'varchar(100)',
                'import_id' => 'int(11)',
                'local_id'  => 'int(11)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->run("DROP TABLE IF EXISTS fcom_import_info");
        $tModel = $this->FCom_Core_Model_ImportExport_Model->table();
        $this->BDb->ddlTableDef(
            $tModel,
            [
                BDb::COLUMNS => [
                    'id'         => 'int unsigned not null auto_increment',
                    'model_name' => 'varchar(255)',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'model_name' => 'UNIQUE(model_name)',
                ]
            ]
        );

        $tSite = $this->FCom_Core_Model_ImportExport_Site->table();
        $this->BDb->ddlTableDef(
            $tSite,
            [
                BDb::COLUMNS => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_code' => 'char(32)',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'site_code' => 'UNIQUE(site_code)',
                ]
            ]
        );

        //Source, model, import id, local id
        $this->BDb->ddlTableDef(
            $this->FCom_Core_Model_ImportExport_Id->table(),
            [
                BDb::COLUMNS => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_id'   => 'int unsigned',
                    'model_id'  => 'int unsigned',
                    'import_id' => 'int unsigned',
                    'local_id'  => 'int unsigned',
                    'relations' => 'text null',
                    'create_at' => 'datetime',
                    'update_at' => 'datetime',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'uk_site_model_import_id' => "UNIQUE (site_id,model_id,import_id)",
                ],
                BDb::CONSTRAINTS => [
                    'model' => ['model_id', $tModel],
                    'site' => ['site_id', $tSite],
                ],
            ]
        );
        if (!$this->BConfig->get('core/cache/default_backend')) {
            $this->_defaultBackend = $this->BCache->getFastestAvailableBackend();
            $this->BConfig->set('core/cache/default_backend', $this->_defaultBackend, false, true);
            $this->BConfig->writeConfigFiles('core');
        }

        $tableExternalConfig = $this->FCom_Core_Model_ExternalConfig->table();

        $this->BDb->ddlTableDef($tableExternalConfig, [
            BDb::COLUMNS => [
                'id'                => 'int(10) unsigned not null auto_increment',
                'source_type'       => 'varchar(50) not null',
                'path'              => 'varchar(255) not null',
                'value'             => 'text not null',
                'site_id'           => 'int(11) unsigned default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS    => [
                'UNQ_external_config' => 'UNIQUE (source_type, path, site_id)',
            ],
        ]);

        $hlpField = $this->FCom_Core_Model_Field;
        $tField = $hlpField->table();
        $tFieldOption = $this->FCom_Core_Model_FieldOption->table();
        $tSet = $this->FCom_Core_Model_Fieldset->table();
        $tSetField = $this->FCom_Core_Model_FieldsetField->table();

        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'id' => "int(10) unsigned NOT NULL AUTO_INCREMENT",
                'field_type' => "varchar(50) NOT NULL",
                'field_code' => "varchar(50) NOT NULL",
                'field_name' => "varchar(255) NOT NULL",
                'table_field_type' => "varchar(20) NOT NULL",
                'admin_input_type' => "varchar(20) NOT NULL DEFAULT 'text'",
                'frontend_label' => "text",
                'frontend_show' => "tinyint(1) NOT NULL DEFAULT '1'",
                'config_json' => "text",
                'sort_order' => "int(11) NOT NULL DEFAULT '0'",
                'system' => "tinyint(1) NOT NULL DEFAULT '0'",
                'multilanguage' => "tinyint(1) NOT NULL DEFAULT '0'",
                'validation' => "varchar(100) DEFAULT NULL",
                'required' => "tinyint(1) NOT NULL DEFAULT '1'",

                // PRODUCTS
                'facet_select' => "enum('No','Exclusive','Inclusive') NOT NULL DEFAULT 'No'",
                'swatch_type' => "char(1) not null default 'N'",

                // CUSTOMERS
                'register_form' => "BOOLEAN DEFAULT 0",
                'account_edit'  => "BOOLEAN DEFAULT 0",

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
                'swatch_info' => 'text default null',
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
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $this->BDb->ddlTableDef($this->FCom_Core_Model_Seq->table(), [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'current_seq_id' => 'varchar(15) not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_entity_type' => 'UNIQUE (entity_type)',
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $this->BDb->ddlTableDef($this->FCom_Core_Model_MediaLibrary->table(), [
            BDb::COLUMNS => [
                'data_json' => BDb::DROP,
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::KEYS => [
                'IDX_create_at' => '(create_at)',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $this->BConfig->set('cookie/session_check_ip', 1, false, true);
        $this->BConfig->writeConfigFiles();
    }

    public function upgrade__0_1_3__0_1_4()
    {
        //Source, model, import id, local id
        $this->BDb->ddlTableDef($this->FCom_Core_Model_ImportExport_Id->table(), [
            BDb::COLUMNS => [
                'id'        => 'int(11)',
                'store_id'  => 'char(32)',
                'model'     => 'varchar(100)',
                'import_id' => 'int(11)',
                'local_id'  => 'int(11)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
        ]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $this->BDb->run("DROP TABLE IF EXISTS fcom_import_info");
        $tModel = $this->FCom_Core_Model_ImportExport_Model->table();
        $this->BDb->ddlTableDef(
            $tModel,
            [
                BDb::COLUMNS => [
                    'id'         => 'int unsigned not null auto_increment',
                    'model_name' => 'varchar(255)',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'model_name' => 'UNIQUE(model_name)',
                ]
            ]
        );

        $tSite = $this->FCom_Core_Model_ImportExport_Site->table();
        $this->BDb->ddlTableDef(
            $tSite,
            [
                BDb::COLUMNS => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_code' => 'char(32)',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'site_code' => 'UNIQUE(site_code)',
                ]
            ]
        );

        //Source, model, import id, local id
        $this->BDb->ddlTableDef(
            $this->FCom_Core_Model_ImportExport_Id->table(),
            [
                BDb::COLUMNS => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_id'   => 'int unsigned',
                    'model_id'  => 'int unsigned',
                    'import_id' => 'int unsigned',
                    'local_id'  => 'int unsigned',
                    'create_at' => 'datetime',
                    'update_at' => 'datetime',
                ],
                BDb::PRIMARY => '(id)',
                BDb::CONSTRAINTS => [
                    'model' => ['model_id', $tModel],
                    'site' => ['site_id', $tSite],
                ],
            ]
        );
    }

    public function upgrade__0_1_5__0_1_6()
    {
        if (!$this->BConfig->get('core/cache/default_backend')) {
            $this->_defaultBackend = $this->BCache->getFastestAvailableBackend();
            $this->BConfig->set('core/cache/default_backend', $this->_defaultBackend, false, true);
            $this->BConfig->writeConfigFiles('core');
        }
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $this->BDb->ddlTableDef($this->FCom_Core_Model_ImportExport_Id->table(), [
            BDb::COLUMNS => [
                'relations' => 'text null',
            ],
            BDb::KEYS => [
                'uk_site_model_import_id' => "UNIQUE (site_id,model_id,import_id)",
            ],
        ]);
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $this->BCache->deleteAll();
    }

    public function upgrade__0_1_8__0_1_9()
    {
        $tMedia = $this->FCom_Core_Model_MediaLibrary->table();
        $this->BDb->ddlTableDef($tMedia, [
            BDb::COLUMNS => [
                'folder' => 'varchar(255) NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_9__0_2_0()
    {
        $origRegex = 'FCom_(AdapterExcel|AdminLiveFeed|AuthorizeNet|Blog|Catalog|CatalogIndex|Checkout|Cms|Customer|CustomerChat|CustomerGroups|CustomField|Disqus|EasyPost|Email|Feedback|FrontendCP|FrontendThemeBootSimple|GoogleWallet|IndexTank|MultiCurrency|MultiLanguage|MultiSite|MultiVendor|MultiWarehouse|Ogone|PaymentBasic|PaymentCC|PaymentIdeal|PaymentStripe|PayPal|ProductCompare|ProductReviews|Promo|Referrals|Sales|SalesTax|SampleData|Seo|ShippingFree|ShippingPlain|ShippingUps|ShopperFields|VirtPackCoreEcom|Wishlist)';

        $tModule = $this->FCom_Core_Model_Module->table();
        $this->BDb->run("
UPDATE {$tModule} SET module_name=replace(module_name, 'FCom_', 'Sellvana_') WHERE module_name REGEXP '^{$origRegex}\$';
UPDATE {$tModule} SET module_name=CASE module_name
  WHEN 'Sellvana_PayPal' THEN 'Sellvana_PaymentPaypal'
  WHEN 'Sellvana_Ogone' THEN 'Sellvana_PaymentOgone'
  WHEN 'Sellvana_AuthorizeNet' THEN 'Sellvana_PaymentAuthorizeNet'
  ELSE module_name END;
        ");

        $configDir = $this->BConfig->get('fs/config_dir');
        foreach (['core', 'local'] as $t) {
            $configFile = $this->BConfig->get("fs/config_file_{$t}", "{$configDir}/{$t}.php");
            $fixed = preg_replace("#{$origRegex}#", 'Sellvana_\1', file_get_contents($configFile));
            $fixed = str_replace(['Sellvana_PayPal', 'Sellvana_Ogone', 'Sellvana_AuthorizeNet'],
                ['Sellvana_PaymentPaypal', 'Sellvana_Ogone', 'Sellvana_PaymentAuthorizeNet'], $fixed);
            file_put_contents($configFile, $fixed);
        }

        $cacheDir = $this->BConfig->get('fs/cache_dir');
        @$this->BUtil->rmdirRecursive_YesIHaveCheckedThreeTimes($cacheDir);
        @$this->BUtil->ensureDir($cacheDir);

        $this->BMigrate->stopMigration();
    }

    public function upgrade__0_5_0_0__0_5_0_1()
    {
        $tableExternalConfig = $this->FCom_Core_Model_ExternalConfig->table();

        $tableDef = [
            BDb::COLUMNS => [
                'id'                => 'int(10) unsigned not null auto_increment',
                'source_type'       => 'varchar(50) not null',
                'path'              => 'varchar(255) not null',
                'value'             => 'text not null',
                'site_id'           => 'int(11) unsigned default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS    => [
                'UNQ_external_config' => 'UNIQUE (source_type, path, site_id)',
            ],
        ];

        $this->BDb->ddlTableDef($tableExternalConfig, $tableDef);
    }

    public function upgrade__0_6_0_0__0_6_1_0()
    {
        $tField = $this->FCom_Core_Model_Field->table();

        $this->BDb->ddlTableDef($tField, [
            BDb::COLUMNS => [
                'field_name' => "varchar(255) NOT NULL",
            ],
        ]);
    }
}
