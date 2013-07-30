<?php

class FCom_Blog_Migrate extends BClass
{
    public function install__0_1_0()
    {
        BDb::ddlTableDef(FCom_Blog_Model_Article::table(), array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',

            ),
            'PRIMARY' => '(id)',
        ));

        BDb::ddlTableDef(FCom_Blog_Model_Tag::table(), array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',

            ),
            'PRIMARY' => '(id)',
        ));

        BDb::ddlTableDef(FCom_Blog_Model_ArticleTag::table(), array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',

            ),
            'PRIMARY' => '(id)',
        ));
    }
}
