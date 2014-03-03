<?php

class FCom_Core_Migrate extends BClass
{
    public function install__0_1_0()
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
                `data_json` text,
                PRIMARY KEY (`id`),
                KEY `folder_file` (`folder`,`file_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        }
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
}
