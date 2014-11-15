<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_AdminChat_Migrate extends BClass
{
    public function install__0_1_4()
    {
        if (!$this->FCom_Core_Model_Module->load('FCom_Admin', 'module_name')) {
            $this->BMigrate->migrateModules('FCom_Admin', true);
        }

        $tChat = $this->FCom_AdminChat_Model_Chat->table();
        $tParticipant = $this->FCom_AdminChat_Model_Participant->table();
        $tHistory = $this->FCom_AdminChat_Model_History->table();
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlTableDef($tChat, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'status' => 'varchar(20)',
                'owner_user_id' => 'int unsigned not null',
                'num_participants' => 'smallint unsigned not null default 0',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_update_at' => '(update_at)',
            ],
            BDb::CONSTRAINTS => [
                'owner' => ['owner_user_id', $tUser],
            ],
        ]);

        $this->BDb->ddlTableDef($tParticipant, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'chat_id' => 'int unsigned not null',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'chat_title' => 'varchar(50) null',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_update_at' => '(update_at)',
            ],
            BDb::CONSTRAINTS => [
                'chat' => ['chat_id', $tChat],
                'user' => ['user_id', $tUser],
            ],
        ]);

        $this->BDb->ddlTableDef($tHistory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'chat_id' => 'int unsigned not null',
                'user_id' => 'int unsigned not null',
                'entry_type' => 'varchar(20) default "text"',
                'text' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_update_at' => '(update_at)',
            ],
            BDb::CONSTRAINTS => [
                'chat' => ['chat_id', $tChat],
                'user' => ['user_id', $tUser],
            ],
        ]);

        $tUserStatus = $this->FCom_AdminChat_Model_UserStatus->table();

        $this->BDb->ddlTableDef($tUserStatus, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'message' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'user' => ['user_id', $tUser],
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tChat = $this->FCom_AdminChat_Model_Chat->table();
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlTableDef($tChat, [
            BDb::COLUMNS => [
                'owner_user_id' => 'int unsigned not null after `status`',
            ],
            BDb::CONSTRAINTS => [
                'owner' => ['owner_user_id', $tUser],
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tHistory = $this->FCom_AdminChat_Model_History->table();
        $this->BDb->ddlTableDef($tHistory, [
            BDb::COLUMNS => [
                'entry_type' => 'varchar(20) default "text" after `user_id`',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tUserStatus = $this->FCom_AdminChat_Model_UserStatus->table();
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlTableDef($tUserStatus, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'message' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'user' => ['user_id', $tUser],
            ],
        ]);
    }
    public function upgrade__0_1_3__0_1_4()
    {
        $tParticipant = $this->FCom_AdminChat_Model_Participant->table();
        $this->BDb->ddlTableDef($tParticipant, [
            BDb::COLUMNS => [
                'chat_title' => 'varchar(50) null after `status`',
            ],
        ]);
    }
}
