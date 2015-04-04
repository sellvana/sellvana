<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Core_Migrate
 *
 * @property FCom_Core_ImportExport $FCom_Core_ImportExport
 * @property FCom_Core_Model_ImportExport_Id $FCom_Core_Model_ImportExport_Id
 * @property FCom_Core_Model_ImportExport_Model $FCom_Core_Model_ImportExport_Model
 * @property FCom_Core_Model_ImportExport_Site $FCom_Core_Model_ImportExport_Site
 * @property FCom_Core_Model_MediaLibrary $FCom_Core_Model_MediaLibrary
 * @property FCom_Core_Model_Seq $FCom_Core_Model_Seq
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 */

class FCom_Core_Migrate extends BClass
{
    public function install__0_2_0()
    {
        $tMediaLibrary = $this->FCom_Core_Model_MediaLibrary->table();
        if (!$this->BDb->ddlTableExists($tMediaLibrary)) {
            $this->BDb->run("
                CREATE TABLE {$tMediaLibrary} (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `folder` varchar(255) NOT NULL,
                  `subfolder` varchar(32) DEFAULT NULL,
                  `file_name` varchar(255) NOT NULL,
                  `file_size` int(11) DEFAULT NULL,
                  `data_serialized` text,
                  `create_at` datetime DEFAULT NULL,
                  `update_at` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `folder_file` (`folder`,`file_name`),
                  KEY `IDX_create_at` (`create_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        }
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
        $this->BDb->ddlTableDef($this->FCom_Core_ImportExport->table(), [
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
        if (!$this->BConfig->get('cache/default_backend')) {
            $this->_defaultBackend = $this->BCache->getFastestAvailableBackend();
            $this->BConfig->set('cache/default_backend', $this->_defaultBackend, false, true);
            $this->BConfig->writeConfigFiles('core');
        }
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
        $this->BDb->ddlTableDef($this->FCom_Core_ImportExport->table(), [
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
        if (!$this->BConfig->get('cache/default_backend')) {
            $this->_defaultBackend = $this->BCache->getFastestAvailableBackend();
            $this->BConfig->set('cache/default_backend', $this->_defaultBackend, false, true);
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
}
