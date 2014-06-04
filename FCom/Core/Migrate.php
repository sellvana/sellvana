<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_Migrate extends BClass
{
    public function install__0_1_6()
    {
        if (!$this->BDb->ddlTableExists('fcom_media_library')) {
            $tMediaLibrary = $this->FCom_Core_Model_MediaLibrary->table();
            $this->BDb->run("
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
        $this->BDb->ddlTableDef($this->FCom_Core_Model_Seq->table(), [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'current_seq_id' => 'varchar(15) not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_entity_type' => 'UNIQUE (entity_type)',
            ],
        ]);
        $this->BConfig->set('cookie/session_check_ip', 1, false, true);
        $this->FCom_Core_Main->writeConfigFiles();
        $this->BDb->ddlTableDef($this->FCom_Core_ImportExport->table(), [
            'COLUMNS' => [
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
                'COLUMNS' => [
                    'id'         => 'int unsigned not null auto_increment',
                    'model_name' => 'varchar(255)',
                ],
                'PRIMARY' => '(id)',
                'KEYS' => [
                    'model_name' => 'UNIQUE(model_name)',
                ]
            ]
        );

        $tSite = $this->FCom_Core_Model_ImportExport_Site->table();
        $this->BDb->ddlTableDef(
            $tSite,
            [
                'COLUMNS' => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_code' => 'char(32)',
                ],
                'PRIMARY' => '(id)',
                'KEYS' => [
                    'site_code' => 'UNIQUE(site_code)',
                ]
            ]
        );

        //Source, model, import id, local id
        $this->BDb->ddlTableDef(
            $this->FCom_Core_Model_ImportExport_Id->table(),
            [
                'COLUMNS' => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_id'   => 'int unsigned',
                    'model_id'  => 'int unsigned',
                    'import_id' => 'int unsigned',
                    'local_id'  => 'int unsigned',
                    'create_at' => 'datetime',
                    'update_at' => 'datetime',
                ],
                'PRIMARY' => '(id)',
                'CONSTRAINTS' => [
                    'Ffk_import_fk_model_id' => "FOREIGN KEY (model_id) REFERENCES {$tModel}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    'Ffk_import_fk_site_id' => "FOREIGN KEY (site_id) REFERENCES {$tSite}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                ],
            ]
        );
        if (!$this->BConfig->get('cache/default_backend')) {
            $this->_defaultBackend = $this->BCache->getFastestAvailableBackend();
            $this->BConfig->set('cache/default_backend', $this->_defaultBackend, false, true);
            $this->FCom_Core_Main->writeConfigFiles('core');
        }
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $this->BDb->ddlTableDef($this->FCom_Core_Model_Seq->table(), [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'entity_type' => 'varchar(15) not null',
                'current_seq_id' => 'varchar(15) not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_entity_type' => 'UNIQUE (entity_type)',
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $this->BDb->ddlTableDef($this->FCom_Core_Model_MediaLibrary->table(), [
            'COLUMNS' => [
                'data_json' => 'DROP',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'KEYS' => [
                'IDX_create_at' => '(create_at)',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $this->BConfig->set('cookie/session_check_ip', 1, false, true);
        $this->FCom_Core_Main->writeConfigFiles();
    }

    public function upgrade__0_1_3__0_1_4()
    {
        //Source, model, import id, local id
        $this->BDb->ddlTableDef($this->FCom_Core_ImportExport->table(), [
            'COLUMNS' => [
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
                'COLUMNS' => [
                    'id'         => 'int unsigned not null auto_increment',
                    'model_name' => 'varchar(255)',
                ],
                'PRIMARY' => '(id)',
                'KEYS' => [
                    'model_name' => 'UNIQUE(model_name)',
                ]
            ]
        );

        $tSite = $this->FCom_Core_Model_ImportExport_Site->table();
        $this->BDb->ddlTableDef(
            $tSite,
            [
                'COLUMNS' => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_code' => 'char(32)',
                ],
                'PRIMARY' => '(id)',
                'KEYS' => [
                    'site_code' => 'UNIQUE(site_code)',
                ]
            ]
        );

        //Source, model, import id, local id
        $this->BDb->ddlTableDef(
            $this->FCom_Core_Model_ImportExport_Id->table(),
            [
                'COLUMNS' => [
                    'id'        => 'int unsigned not null auto_increment',
                    'site_id'   => 'int unsigned',
                    'model_id'  => 'int unsigned',
                    'import_id' => 'int unsigned',
                    'local_id'  => 'int unsigned',
                    'create_at' => 'datetime',
                    'update_at' => 'datetime',
                ],
                'PRIMARY' => '(id)',
                'CONSTRAINTS' => [
                    'Ffk_import_fk_model_id' => "FOREIGN KEY (model_id) REFERENCES {$tModel}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                    'Ffk_import_fk_site_id' => "FOREIGN KEY (site_id) REFERENCES {$tSite}(id) ON DELETE CASCADE ON UPDATE CASCADE",
                ],
            ]
        );
    }

    public function upgrade__0_1_5__0_1_6()
    {
        if (!$this->BConfig->get('cache/default_backend')) {
            $this->_defaultBackend = $this->BCache->getFastestAvailableBackend();
            $this->BConfig->set('cache/default_backend', $this->_defaultBackend, false, true);
            $this->FCom_Core_Main->writeConfigFiles('core');
        }
    }

    public function upgrade__0_1_6__0_1_7()
    {
        $this->BDb->ddlTableDef(
            $this->FCom_Core_Model_ImportExport_Id->table(),
            [
                'COLUMNS' => ['relations' => 'text null'],
                'KEYS' => ['uk_site_model_import_id' => "UNIQUE (site_id,model_id,import_id)"],
            ]
        );
    }

    public function upgrade__0_1_7__0_1_8()
    {
        $this->BCache->deleteAll();
    }
}
