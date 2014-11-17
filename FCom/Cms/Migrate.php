<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Cms_Migrate
 *
 * @property FCom_Cms_Model_Block $FCom_Cms_Model_Block
 * @property FCom_Cms_Model_BlockHistory $FCom_Cms_Model_BlockHistory
 * @property FCom_Cms_Model_Form $FCom_Cms_Model_Form
 * @property FCom_Cms_Model_FormData $FCom_Cms_Model_FormData
 * @property FCom_Cms_Model_Nav $FCom_Cms_Model_Nav
 * @property FCom_Cms_Model_Page $FCom_Cms_Model_Page
 * @property FCom_Cms_Model_PageHistory $FCom_Cms_Model_PageHistory
 */

class FCom_Cms_Migrate extends BClass
{
    public function install__0_1_3()
    {
/*
        $tNav = $this->FCom_Cms_Model_Nav->table();
        $this->BDb->run("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
        ");
        $tPage = $this->FCom_Cms_Model_Page->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tPage} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `handle` varchar(255)  NOT NULL,
            `title` varchar(255)  NOT NULL,
            `content` text  NOT NULL,
            `layout_update` text ,
            `create_dt` datetime DEFAULT NULL,
            `update_dt` datetime DEFAULT NULL,
            `version` int(11) unsigned NOT NULL,
            `meta_title` text ,
            `meta_description` text ,
            `meta_keywords` text ,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

        ");

        $tPageHistory = $this->FCom_Cms_Model_PageHistory->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tPageHistory} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `page_id` int(10) unsigned NOT NULL,
            `version` int(11) unsigned NOT NULL,
            `user_id` int(11) unsigned null,
            `username` varchar(50)  NULL,
            `data` text  NOT NULL,
            `comments` text  NOT NULL,
            `ts` datetime not null,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
        ");
*/
        $tBlock = $this->FCom_Cms_Model_Block->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tBlock} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `handle` varchar(100)  NOT NULL,
            `description` text ,
            `renderer` varchar(100) null,
            `content` text ,
            `layout_update` text ,
            `version` int(11) NOT NULL,
            `create_at` datetime DEFAULT NULL,
            `update_at` datetime DEFAULT NULL,
            `page_enabled` TINYINT DEFAULT 0 NOT NULL,
            `page_url` VARCHAR (100) NULL,
            `page_title` TEXT NULL,
            `meta_title` TEXT NULL,
            `meta_description` TEXT NULL,
            `meta_keywords` TEXT NULL,
            `modified_time` int unsigned,
            PRIMARY KEY (`id`),
            UNIQUE KEY `UNQ_handle` (`handle`),
            UNIQUE KEY `UNQ_page_url` (`page_enabled`,`page_url`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
        ");

        $tBlock = $this->FCom_Cms_Model_Block->table();
        $tBlockHistory = $this->FCom_Cms_Model_BlockHistory->table();
        $this->BDb->run("
            CREATE TABLE IF NOT EXISTS {$tBlockHistory} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `block_id` int(10) unsigned NOT NULL,
            `version` int(11) unsigned NOT NULL,
            `user_id` int(11) unsigned null,
            `username` varchar(50)  NULL,
            `data` text  NOT NULL,
            `comments` text  NOT NULL,
            `ts` datetime not null,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tBlockHistory}_block` FOREIGN KEY (`block_id`) REFERENCES {$tBlock} (`id`) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;
        ");

        $tForm = $this->FCom_Cms_Model_Form->table();
        $tFormData = $this->FCom_Cms_Model_FormData->table();

        $this->BDb->ddlTableDef($tForm, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'form_name' => 'varchar(100)',
                'form_url' => 'varchar(255)',
                'form_status' => "char(1) not null default 'P'",
                'validation_rules' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_form_name' => 'UNIQUE (form_name)'
            ],
        ]);

        $this->BDb->ddlTableDef($tFormData, [
            BDb::COLUMNS => [
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
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'form' => ['form_id', $tForm],
            ],
        ]);

        //$this->BDb->run("REPLACE INTO {$tNav} (id,id_path) VALUES (1,1)");
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $this->BDb->ddlTableDef($this->FCom_Cms_Model_Block->table(), [
            BDb::COLUMNS => [
                'renderer' => 'varchar(100) null after content',
                'page_enabled' => 'TINYINT DEFAULT 0 NOT NULL',
                'page_url' => 'VARCHAR (100) NULL',
                'page_title' => 'TEXT NULL',
                'meta_title' => 'TEXT NULL',
                'meta_description' => 'TEXT NULL',
                'meta_keywords' => 'TEXT NULL',
                'modified_time' => 'int unsigned',
            ],
            BDb::KEYS => [
                'UNQ_handle' => 'UNIQUE (handle)',
                'UNQ_page_url' => 'UNIQUE (page_enabled, page_url)',
            ],
        ]);
        /*
        $this->BDb->ddlTableDef($this->FCom_Cms_Model_Nav->table(), array(
            BDb::COLUMNS => array(
                'contents' => 'RENAME content text null',
                'renderer' => 'varchar(100) null after content',
            ),
        ));
        $this->BDb->ddlTableDef($this->FCom_Cms_Model_Page->table(), array(
            BDb::COLUMNS => array(
                'renderer' => 'varchar(100) null after content',
            ),
            BDb::KEYS => array(
                'UNQ_handle' => 'UNIQUE (handle)',
            ),
        ));
        */

/*
        $homePage = $this->FCom_Cms_Model_Page->create(array(
            'handle' => 'home',
            'title' => 'Home Page',
            'content' => file_get_contents(__DIR__.'/Frontend/views/_default/home.twig.html'),
        ));
*/
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $table = $this->FCom_Cms_Model_Block->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                  'create_dt'      => 'RENAME create_at datetime DEFAULT NULL',
                  'update_dt'      => 'RENAME update_at datetime DEFAULT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tForm = $this->FCom_Cms_Model_Form->table();
        $tFormData = $this->FCom_Cms_Model_FormData->table();

        $this->BDb->ddlTableDef($tForm, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'form_name' => 'varchar(100)',
                'form_url' => 'varchar(255)',
                'form_status' => "char(1) not null default 'P'",
                'validation_rules' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_form_name' => 'UNIQUE (form_name)',
            ],
        ]);

        $this->BDb->ddlTableDef($tFormData, [
            BDb::COLUMNS => [
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
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
            ],
            BDb::CONSTRAINTS => [
                'form' => ['form_id', $tForm],
            ],
        ]);
    }

    public function upgrade__0_1_3__0_1_4()
    {
        $tBlock = $this->FCom_Cms_Model_Block->table();
        $this->BDb->ddlTableDef($tBlock, [
            BDb::COLUMNS => [
                'form_enable' => 'tinyint not null default 0',
                'form_fields' => 'text default null',
            ],
        ]);
    }

    public function upgrade__0_1_4__0_1_5()
    {
        $tBlock = $this->FCom_Cms_Model_Block->table();
        $this->BDb->ddlTableDef($tBlock, [
            BDb::COLUMNS => [
                'form_email' => 'varchar(20)',
                'form_custom_email' => 'varchar(100)',
            ],
        ]);
    }
}
