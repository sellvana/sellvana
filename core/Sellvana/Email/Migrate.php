<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Email_Migrate
 *
 * @property Sellvana_Email_Model_Message $Sellvana_Email_Model_Message
 * @property Sellvana_Email_Model_Pref $Sellvana_Email_Model_Pref
 */

class Sellvana_Email_Migrate extends BClass
{
    public function install__0_1_3()
    {
        $tPref = $this->Sellvana_Email_Model_Pref->table();
        $tMessage = $this->Sellvana_Email_Model_Message->table();

        $this->BDb->ddlTableDef($tPref, [
            BDb::COLUMNS => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'email' => 'varchar(100)  NOT NULL',
                'unsub_all' => 'tinyint(4) NOT NULL',
                'sub_newsletter' => 'tinyint(4) NOT NULL',
                'create_at' => 'datetime NOT NULL',
                'update_at' => 'datetime NOT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'email' => 'UNIQUE (email)',
            ],
        ]);
        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'view_name' => 'varchar(255) default null',
                'recipient' => 'varchar(100) NOT NULL',
                'subject' => 'varchar(255) NOT NULL',
                'body' => 'MEDIUMTEXT',
                'status' => "varchar(20) not null default 'new'",
                'error_message' => 'text',
                'num_attempts' => 'smallint not null default 0',
                'data_serialized' => 'text',
                'create_at' => 'datetime NOT NULL',
                'resent_at' => 'datetime NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'recipient' => '(recipient)',
                'IDX_view_name' => '(view_name)',
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tMessage = $this->Sellvana_Email_Model_Message->table();

        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
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
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'recipient' => '(recipient)',
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tPref = $this->Sellvana_Email_Model_Pref->table();
        $tMessage = $this->Sellvana_Email_Model_Message->table();

        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                  'create_dt' => 'RENAME create_at datetime NOT NULL',
                  'resent_dt' => 'RENAME resent_at datetime NULL',
            ],
        ]);
        $this->BDb->ddlTableDef($tPref, [
            BDb::COLUMNS => [
                  'create_dt' => 'RENAME create_at datetime NOT NULL',
                  'update_dt' => 'RENAME update_at datetime NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tMessage = $this->Sellvana_Email_Model_Message->table();
        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'view_name' => 'varchar(255) default null',
            ],
            BDb::KEYS => [
                'IDX_view_name' => '(view_name)',
            ],
        ]);
    }
}
