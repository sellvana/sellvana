<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Blog_Migrate
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Blog_Model_Category $Sellvana_Blog_Model_Category
 * @property Sellvana_Blog_Model_Post $Sellvana_Blog_Model_Post
 * @property Sellvana_Blog_Model_PostCategory $Sellvana_Blog_Model_PostCategory
 * @property Sellvana_Blog_Model_PostTag $Sellvana_Blog_Model_PostTag
 * @property Sellvana_Blog_Model_Tag $Sellvana_Blog_Model_Tag
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 */

class Sellvana_Blog_Migrate extends BClass
{
    public function install__0_1_4()
    {
        if (!$this->FCom_Core_Model_Module->load('FCom_Admin', 'module_name')) {
            $this->BMigrate->migrateModules('FCom_Admin', true);
        }

        $tPost = $this->Sellvana_Blog_Model_Post->table();
        $tTag = $this->Sellvana_Blog_Model_Tag->table();
        $tPostTag = $this->Sellvana_Blog_Model_PostTag->table();
        $tUser = $this->FCom_Admin_Model_User->table();
        $tCategory = $this->Sellvana_Blog_Model_Category->table();
        $tPostCategory = $this->Sellvana_Blog_Model_PostCategory->table();

        $this->BDb->ddlTableDef($tPost, [
            BDb::COLUMNS => [
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
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_url_key' => 'UNIQUE (url_key)',
                'IDX_status_create_at' => '(status, create_at)',
                'IDX_create_ym' => '(status, create_ym)',
            ],
            BDb::CONSTRAINTS => [
                'author' => ['author_user_id', $tUser],
            ],
        ]);

        $this->BDb->ddlTableDef($tTag, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'tag_key' => 'varchar(50)',
                'tag_name' => 'varchar(50)',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_tag_key' => 'UNIQUE (tag_key)',
            ],
        ]);

        $this->BDb->ddlTableDef($tPostTag, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'tag_id' => 'int unsigned not null',
                'post_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_post_tag' => '(post_id, tag_id)',
            ],
            BDb::CONSTRAINTS => [
                'post' => ['post_id', $tPost],
                'tag' => ['tag_id', $tTag],
            ],
        ]);


        $this->BDb->ddlTableDef($tCategory, [
            BDb::COLUMNS => [
                'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'name'    => 'varchar(255) NOT NULL',
                'url_key'    => 'varchar(255) NOT NULL',
                'description'    => 'text NULL',
            ],
            BDb::PRIMARY => '(id)',
        ]);
        $this->BDb->ddlTableDef($tPostCategory, [
            BDb::COLUMNS => [
                'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                'category_id'    => 'INT(10) UNSIGNED NOT NULL',
                'post_id'   => 'INT(10) UNSIGNED NOT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'post_id' => 'UNIQUE (`post_id`,`category_id`)',
                'category_id__post_id' => '(`category_id`,`post_id`)',
            ],
            BDb::CONSTRAINTS => [
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
        $tCategory = $this->Sellvana_Blog_Model_Category->table();
        $tPost = $this->Sellvana_Blog_Model_Post->table();
        $tPostCategory = $this->Sellvana_Blog_Model_PostCategory->table();

        $this->BDb->ddlTableDef($tCategory, [
                BDb::COLUMNS => [
                    'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'name'    => 'varchar(255) NOT NULL',
                    'url_key'    => 'varchar(255) NOT NULL',
                    'description'    => 'text NULL',
                ],
                BDb::PRIMARY => '(id)',
            ]);
        $this->BDb->ddlTableDef($tPostCategory, [
                BDb::COLUMNS => [
                    'id' => 'INT(10) UNSIGNED NOT NULL AUTO_INCREMENT',
                    'category_id'    => 'INT(10) UNSIGNED NOT NULL',
                    'post_id'   => 'INT(10) UNSIGNED NOT NULL',
                ],
                BDb::PRIMARY => '(id)',
                BDb::KEYS => [
                    'post_id' => 'UNIQUE (`post_id`,`category_id`)',
                    'category_id__post_id' => '(`category_id`,`post_id`)',
                ],
                BDb::CONSTRAINTS => [
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
