<?php

class FCom_Email_Migrate extends BClass
{
    public function install__0_1_0()
    {
        BDb::ddlTableDef(FCom_Email_Model_Pref::table(), array(
            'COLUMNS' => array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'email' => 'varchar(100) COLLATE utf8_unicode_ci NOT NULL',
                'unsub_all' => 'tinyint(4) NOT NULL',
                'sub_newsletter' => 'tinyint(4) NOT NULL',
                'create_dt' => 'datetime NOT NULL',
                'update_dt' => 'datetime NOT NULL',
            ),
            'PRIMARY' =>'(`id`)',
            'KEYS' => array(
                'email' => 'UNIQUE (`email`)',
            ),
        ));
    }

    public function upgrade__0_1_0__0_1_1()
    {
        BDb::ddlTableDef(FCom_Email_Model_Message::table(), array(
            'COLUMNS' => array(
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'recipient' => 'varchar(100) NOT NULL',
                'subject' => 'varchar(255) NOT NULL',
                'body' => 'MEDIUMTEXT',
                'status' => "varchar(20) not null default 'new'",
                'error_message' => 'text',
                'num_attempts' => 'smallint not null default 0',
                'data_serialized' => 'text',
                'create_dt' => 'datetime NOT NULL',
                'resent_dt' => 'datetime NULL',
            ),
            'PRIMARY' =>'(`id`)',
            'KEYS' => array(
                'recipient' => '(`recipient`)',
            ),
        ));
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $table = FCom_Email_Model_Message::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'resent_dt'      => 'RENAME resent_at datetime NULL',
            ),
        ));
        $table = FCom_Email_Model_Pref::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'create_dt'      => 'RENAME create_at datetime NOT NULL',
                  'update_dt'      => 'RENAME update_at datetime NOT NULL',
            ),
        ));
    }
}
