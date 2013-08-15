<?php

class FCom_Blog_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tPost = FCom_Blog_Model_Post::table();
        $tTag = FCom_Blog_Model_Tag::table();
        $tPostTag = FCom_Blog_Model_PostTag::table();
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlTableDef($tPost, array(
            'COLUMNS' => array(
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
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_url_key' => '(url_key)',
                'IDX_status_create_at' => '(status, create_at)',
                'IDX_create_ym' => '(status, create_ym)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tPost}_author" => "FOREIGN KEY (author_user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));

        BDb::ddlTableDef($tTag, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'tag_key' => 'varchar(50)',
                'tag_name' => 'varchar(50)',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_tag_key' => 'UNIQUE (tag_key)',
            ),
        ));

        BDb::ddlTableDef($tPostTag, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'tag_id' => 'int unsigned not null',
                'post_id' => 'int unsigned not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_post_tag' => '(post_id, tag_id)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tPostTag}_post" => "FOREIGN KEY (post_id) REFERENCES {$tPost} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tPostTag}_tag" => "FOREIGN KEY (tag_id) REFERENCES {$tTag} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
    }

    public function upgrade__0_1_0__0_1_1()
    {
        BDb::run("
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS fcom_blog_article_tag;
DROP TABLE IF EXISTS fcom_blog_tag;
DROP TABLE IF EXISTS fcom_blog_article;
SET FOREIGN_KEY_CHECKS=1;
        ");
        $this->install__0_1_1();
    }
}