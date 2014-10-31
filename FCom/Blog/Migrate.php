<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Blog_Migrate extends BClass
{
    public function install__0_1_4()
    {
        if (!$this->FCom_Core_Model_Module->load('FCom_Admin', 'module_name')) {
            $this->BMigrate->migrateModules('FCom_Admin', true);
        }

        $tPost = $this->FCom_Blog_Model_Post->table();
        $tTag = $this->FCom_Blog_Model_Tag->table();
        $tPostTag = $this->FCom_Blog_Model_PostTag->table();
        $tUser = $this->FCom_Admin_Model_User->table();
        $tCategory = $this->FCom_Blog_Model_Category->table();
        $tPostCategory = $this->FCom_Blog_Model_PostCategory->table();

        $this->BDb->ddlTableDef($tPost, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'author_user_id' => 'int unsigned not null',
                'status' => "varchar(10) not null default 'pending'",
                'title' => 'varchar(255) not null',
                'url_key' => 'varchar(255) not null',
                'preview' => 'text',
                'content' => 'text',
                'data_serialized' => 'text',
                'meta_title' => 'text',
                'meta_description' => 'text',
                'meta_keywords' => 'text',
                'create_ym' => 'char(6)',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_url_key' => 'UNIQUE (url_key)',
                'IDX_status_create_at' => '(status, create_at)',
                'IDX_create_ym' => '(status, create_ym)',
            ],
            'CONSTRAINTS' => [
                'author' => ['author_user_id', $tUser],
            ],
        ]);

        $this->BDb->ddlTableDef($tTag, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'tag_key' => 'varchar(50)',
                'tag_name' => 'varchar(50)',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_tag_key' => 'UNIQUE (tag_key)',
            ],
        ]);

        $this->BDb->ddlTableDef($tPostTag, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'tag_id' => 'int unsigned not null',
                'post_id' => 'int unsigned not null',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_post_tag' => '(post_id, tag_id)',
            ],
            'CONSTRAINTS' => [
                'post' => ['post_id', $tPost],
                'tag' => ['tag_id', $tTag],
            ],
        ]);


        $this->BDb->ddlTableDef($tCategory, [
            'COLUMNS' => [
                'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'name'    => 'varchar(255) NOT NULL',
                'url_key'    => 'varchar(255) NOT NULL',
                'description'    => 'text NULL',
            ],
            'PRIMARY' => '(id)',
        ]);
        $this->BDb->ddlTableDef($tPostCategory, [
            'COLUMNS' => [
                'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'category_id'    => 'INT(10) UNSIGNED NOT NULL',
                'post_id'   => 'INT(10) UNSIGNED NOT NULL',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'post_id' => 'UNIQUE (`post_id`,`category_id`)',
                'category_id__post_id' => '(`category_id`,`post_id`)',
            ],
            'CONSTRAINTS' => [
                'category' => ['category_id', $tCategory],
                'post' => ['post_id', $tPost],
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $this->BDb->run("
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS fcom_blog_article_tag;
DROP TABLE IF EXISTS fcom_blog_tag;
DROP TABLE IF EXISTS fcom_blog_article;
SET FOREIGN_KEY_CHECKS=1;
        ");
        $this->install__0_1_1();
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tCategory = $this->FCom_Blog_Model_Category->table();
        $tPost = $this->FCom_Blog_Model_Post->table();
        $tPostCategory = $this->FCom_Blog_Model_PostCategory->table();

        $this->BDb->ddlTableDef($tCategory, [
                'COLUMNS' => [
                    'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'name'    => 'varchar(255) NOT NULL',
                    'url_key'    => 'varchar(255) NOT NULL',
                    'description'    => 'text NULL',
                ],
                'PRIMARY' => '(id)',
            ]);
        $this->BDb->ddlTableDef($tPostCategory, [
                'COLUMNS' => [
                    'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'category_id'    => 'INT(10) UNSIGNED NOT NULL',
                    'post_id'   => 'INT(10) UNSIGNED NOT NULL',
                ],
                'PRIMARY' => '(id)',
                'KEYS' => [
                    'post_id' => 'UNIQUE (`post_id`,`category_id`)',
                    'category_id__post_id' => '(`category_id`,`post_id`)',
                ],
                'CONSTRAINTS' => [
                    'category' => ['category_id', $tCategory],
                    'post' => ['post_id', $tPost],
                ],
            ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {

    }

    public function upgrade__0_1_3__0_1_4()
    {
        //$this->BDb->run("RENAME TABLE fcom_blog_category_post TO fcom_blog_post_category");
    }
}
