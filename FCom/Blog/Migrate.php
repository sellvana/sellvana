<?php

class FCom_Blog_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tArticle = FCom_Blog_Model_Article::table();
        $tTag = FCom_Blog_Model_Tag::table();
        $tArticleTag = FCom_Blog_Model_ArticleTag::table();
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlTableDef($tArticle, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'author_user_id' => 'int unsigned not null',
                'status' => "varchar(10) not null default 'pending'",
                'title' => 'varchar(255) not null',
                'url_key' => 'varchar(255) not null',
                'preview' => 'text',
                'content' => 'text',
                'meta_title' => 'text',
                'meta_description' => 'text',
                'meta_keywords' => 'text',
                'year_month' => 'char(6)',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_url_key' => '(url_key)',
                'IDX_status_create_at' => '(status, create_at)',
                'IDX_year_month' => '(status, year_month)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tArticle}_author" => "FOREIGN KEY (author_user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
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

        BDb::ddlTableDef($tArticleTag, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'tag_id' => 'int unsigned not null',
                'article_id' => 'int unsigned not null',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'UNQ_article_tag' => '(article_id, tag_id)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tArticleTag}_article" => "FOREIGN KEY (article_id) REFERENCES {$tArticle} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tArticleTag}_tag" => "FOREIGN KEY (tag_id) REFERENCES {$tTag} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
    }
}
