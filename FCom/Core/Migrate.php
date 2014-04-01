<?php

class FCom_Core_Migrate extends BClass
{
    public function install__0_1_5()
    {
        if (BDb::ddlTableExists('buckyball_module')) {
            BDb::run("
                replace into fcom_module select * from buckyball_module;
                drop table buckyball_module;
            ");
            return;
        }
        if (!BDb::ddlTableExists('fcom_media_library')) {
            $tMediaLibrary = FCom_Core_Model_MediaLibrary::table();
            BDb::run("
                CREATE TABLE {$tMediaLibrary} (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `folder` varchar(32) NOT NULL,
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
        BDb::ddlTableDef(FCom_Core_Model_Seq::table(), array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'current_seq_id' => 'varchar(15) not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_entity_type' => 'UNIQUE (entity_type)',
            ),
        ));
        BConfig::i()->set('cookie/session_check_ip', 1, false, true);
        FCom_Core_Main::i()->writeConfigFiles();
        BDb::ddlTableDef(FCom_Core_ImportExport::table(), array(
            'COLUMNS' => array(
                'id'        => 'int(11)',
                'store_id'  => 'char(32)',
                'model'     => 'varchar(100)',
                'import_id' => 'int(11)',
                'local_id'  => 'int(11)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
        ));

        BDb::run("DROP TABLE IF EXISTS fcom_import_info");
        $tModel = FCom_Core_Model_ImportExport_Model::table();
        BDb::ddlTableDef(
            $tModel,
            array(
                'COLUMNS' => array(
                    'id'         => 'int unsigned not null auto_increment',
                    'model_name' => 'varchar(255)',
                ),
                'PRIMARY' => '(id)',
                'KEYS' => array(
                    'model_name' => 'UNIQUE(model_name)',
                )
            )
        );

        $tSite = FCom_Core_Model_ImportExport_Site::table();
        BDb::ddlTableDef(
            $tSite,
            array(
                'COLUMNS' => array(
                    'id'        => 'int unsigned not null auto_increment',
                    'site_code' => 'char(32)',
                ),
                'PRIMARY' => '(id)',
                'KEYS' => array(
                    'site_code' => 'UNIQUE(site_code)',
                )
            )
        );

        //Source, model, import id, local id
        BDb::ddlTableDef(
            FCom_Core_Model_ImportExport_Id::table(),
            array(
                'COLUMNS' => array(
                    'id'        => 'int unsigned not null auto_increment',
                    'site_id'   => 'int unsigned',
                    'model_id'  => 'int unsigned',
                    'import_id' => 'int unsigned',
                    'local_id'  => 'int unsigned',
                    'create_at' => 'datetime',
                    'update_at' => 'datetime',
                ),
                'PRIMARY' => '(id)',
                'CONSTRAINTS' => array(
                    'Ffk_import_fk_model_id' => "FOREIGN KEY (model_id) REFERENCES {$tModel}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    'Ffk_import_fk_site_id' => "FOREIGN KEY (site_id) REFERENCES {$tSite}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                ),
            )
        );
    }

    public function upgrade__0_1_0__0_1_1()
    {
        BDb::ddlTableDef(FCom_Core_Model_Seq::table(), array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'current_seq_id' => 'varchar(15) not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_entity_type' => 'UNIQUE (entity_type)',
            ),
        ));
    }

    public function upgrade__0_1_1__0_1_2()
    {
        BDb::ddlTableDef(FCom_Core_Model_MediaLibrary::table(), array(
            'COLUMNS' => array(
                'data_json' => 'DROP',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'KEYS' => array(
                'IDX_create_at' => '(create_at)',
            ),
        ));
    }

    public function upgrade__0_1_2__0_1_3()
    {
        BConfig::i()->set('cookie/session_check_ip', 1, false, true);
        FCom_Core_Main::i()->writeConfigFiles();
    }

    public function upgrade__0_1_3__0_1_4()
    {
        //Source, model, import id, local id
        BDb::ddlTableDef(FCom_Core_ImportExport::table(), array(
            'COLUMNS' => array(
                'id'        => 'int(11)',
                'store_id'  => 'char(32)',
                'model'     => 'varchar(100)',
                'import_id' => 'int(11)',
                'local_id'  => 'int(11)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
        ));
    }

    public function upgrade__0_1_4__0_1_5()
    {
        BDb::run("DROP TABLE IF EXISTS fcom_import_info");
        $tModel = FCom_Core_Model_ImportExport_Model::table();
        BDb::ddlTableDef(
            $tModel,
            array(
                'COLUMNS' => array(
                    'id'         => 'int unsigned not null auto_increment',
                    'model_name' => 'varchar(255)',
                ),
                'PRIMARY' => '(id)',
                'KEYS' => array(
                    'model_name' => 'UNIQUE(model_name)',
                )
            )
        );

        $tSite = FCom_Core_Model_ImportExport_Site::table();
        BDb::ddlTableDef(
            $tSite,
            array(
                'COLUMNS' => array(
                    'id'        => 'int unsigned not null auto_increment',
                    'site_code' => 'char(32)',
                ),
                'PRIMARY' => '(id)',
                'KEYS' => array(
                    'site_code' => 'UNIQUE(site_code)',
                )
            )
        );

        //Source, model, import id, local id
        BDb::ddlTableDef(
            FCom_Core_Model_ImportExport_Id::table(),
            array(
                'COLUMNS' => array(
                    'id'        => 'int unsigned not null auto_increment',
                    'site_id'   => 'int unsigned',
                    'model_id'  => 'int unsigned',
                    'import_id' => 'int unsigned',
                    'local_id'  => 'int unsigned',
                    'create_at' => 'datetime',
                    'update_at' => 'datetime',
                ),
                'PRIMARY' => '(id)',
                'CONSTRAINTS' => array(
                    'Ffk_import_fk_model_id' => "FOREIGN KEY (model_id) REFERENCES {$tModel}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    'Ffk_import_fk_site_id' => "FOREIGN KEY (site_id) REFERENCES {$tSite}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                ),
            )
        );
    }

    public function upgrade__0_1_5__0_1_6()
    {
        if (!BConfig::i()->get('cache/default_backend')) {
            $this->_defaultBackend = BCache::i()->getFastestAvailableBackend();
            BConfig::i()->set('cache/default_backend', $this->_defaultBackend, false, true);
            FCom_Core_Main::i()->writeConfigFiles('core');
        }
    }
}
