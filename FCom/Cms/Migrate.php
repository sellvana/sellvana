<?php

class FCom_Cms_Migrate extends BClass
{
    public function install__0_1_0()
    {
/*
        $tNav = FCom_Cms_Model_Nav::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tNav} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `parent_id` int(10) unsigned DEFAULT NULL,
            `id_path` varchar(100) NOT NULL,
            `node_name` varchar(255) NOT NULL,
            `full_name` text NOT NULL,
            `url_key` varchar(255) NOT NULL,
            `url_path` varchar(255) NOT NULL,
            `url_href` varchar(255) NOT NULL,
            `sort_order` int(10) unsigned NOT NULL,
            `num_children` int(10) unsigned DEFAULT NULL,
            `num_descendants` int(10) unsigned DEFAULT NULL,
            `node_type` varchar(20) DEFAULT NULL,
            `reference` varchar(255) DEFAULT NULL,
            `contents` text,
            `layout_update` text,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tNav}_parent` FOREIGN KEY (`parent_id`) REFERENCES {$tNav} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
        $tPage = FCom_Cms_Model_Page::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tPage} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `handle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
            `content` text COLLATE utf8_unicode_ci NOT NULL,
            `layout_update` text COLLATE utf8_unicode_ci,
            `create_dt` datetime DEFAULT NULL,
            `update_dt` datetime DEFAULT NULL,
            `version` int(11) unsigned NOT NULL,
            `meta_title` text COLLATE utf8_unicode_ci,
            `meta_description` text COLLATE utf8_unicode_ci,
            `meta_keywords` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        ");

        $tPageHistory = FCom_Cms_Model_PageHistory::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tPageHistory} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `page_id` int(10) unsigned NOT NULL,
            `version` int(11) unsigned NOT NULL,
            `user_id` int(11) unsigned null,
            `username` varchar(50) COLLATE utf8_unicode_ci NULL,
            `data` text COLLATE utf8_unicode_ci NOT NULL,
            `comments` text COLLATE utf8_unicode_ci NOT NULL,
            `ts` datetime not null,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
*/
        $tBlock = FCom_Cms_Model_Block::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tBlock} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `handle` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
            `description` text COLLATE utf8_unicode_ci,
            `content` text COLLATE utf8_unicode_ci,
            `layout_update` text COLLATE utf8_unicode_ci,
            `version` int(11) NOT NULL,
            `create_dt` datetime DEFAULT NULL,
            `update_dt` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");

        $tBlock = FCom_Cms_Model_Block::table();
        $tBlockHistory = FCom_Cms_Model_BlockHistory::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tBlockHistory} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `block_id` int(10) unsigned NOT NULL,
            `version` int(11) unsigned NOT NULL,
            `user_id` int(11) unsigned null,
            `username` varchar(50) COLLATE utf8_unicode_ci NULL,
            `data` text COLLATE utf8_unicode_ci NOT NULL,
            `comments` text COLLATE utf8_unicode_ci NOT NULL,
            `ts` datetime not null,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tBlockHistory}_block` FOREIGN KEY (`block_id`) REFERENCES {$tBlock} (`id`) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");

        BDb::run("REPLACE INTO {$tNav} (id,id_path) VALUES (1,1)");
    }

    public function upgrade__0_1_0__0_1_1()
    {
        BDb::ddlTableDef(FCom_Cms_Model_Block::table(), array(
            'COLUMNS' => array(
                'renderer' => 'varchar(100) null after content',
                'page_enabled' => 'TINYINT DEFAULT 0 NOT NULL',
                'page_url' => 'VARCHAR (100) NULL',
                'page_title' => 'TEXT NULL',
                'meta_title' => 'TEXT NULL',
                'meta_description' => 'TEXT NULL',
                'meta_keywords' => 'TEXT NULL',
                'modified_time' => 'int unsigned',
            ),
            'KEYS' => array(
                'UNQ_handle' => 'UNIQUE (handle)',
                'UNQ_page_url' => 'UNIQUE (page_enabled, page_url)',
            ),
        ));
        /*
        BDb::ddlTableDef(FCom_Cms_Model_Nav::table(), array(
            'COLUMNS' => array(
                'contents' => 'RENAME content text null',
                'renderer' => 'varchar(100) null after content',
            ),
        ));
        BDb::ddlTableDef(FCom_Cms_Model_Page::table(), array(
            'COLUMNS' => array(
                'renderer' => 'varchar(100) null after content',
            ),
            'KEYS' => array(
                'UNQ_handle' => 'UNIQUE (handle)',
            ),
        ));
        */

/*
        $homePage = FCom_Cms_Model_Page::i()->create(array(
            'handle' => 'home',
            'title' => 'Home Page',
            'content' => file_get_contents(__DIR__.'/Frontend/views/_default/home.twig.html'),
        ));
*/
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $table = FCom_Cms_Model_Block::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'create_dt'      => 'RENAME create_at datetime DEFAULT NULL',
                  'update_dt'      => 'RENAME update_at datetime DEFAULT NULL',
            ),
        ));
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tForm = FCom_Cms_Model_Form::table();
        $tFormData = FCom_Cms_Model_FormData::table();

        BDb::ddlTableDef($tForm, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'form_name' => 'varchar(100)',
                'form_url' => 'varchar(255)',
                'form_status' => "char(1) not null default 'P'",
                'validation_rules' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_form_name' => 'UNIQUE (form_name)',
            ),
        ));

        BDb::ddlTableDef($tFormData, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'form_id' => 'int unsigned not null',
                'customer_id' => 'int unsigned',
                'session_id' => 'varchar(100)',
                'remote_ip' => 'varchar(15)',
                'post_status' => "char(1) not null default 'N'",
                'email' => 'varchar(100)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
                'data_serialized' => 'text',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
            ),
            'CONSTRAINTS' => array(
                "FK_{$tFormData}_form" => "FOREIGN KEY (form_id) REFERENCES {$tForm} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
    }
}
