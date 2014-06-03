<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_AdminChat_Migrate extends BClass
{
    public function install__0_1_4()
    {
        if (!$this->FCom_Core_Model_Module->load('FCom_Admin', 'module_name')) {
            BMigrate::i()->migrateModules('FCom_Admin', true);
        }

        $tChat = $this->FCom_AdminChat_Model_Chat->table();
        $tParticipant = $this->FCom_AdminChat_Model_Participant->table();
        $tHistory = $this->FCom_AdminChat_Model_History->table();
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlTableDef($tChat, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'status' => 'varchar(20)',
                'owner_user_id' => 'int unsigned not null',
                'num_participants' => 'smallint unsigned not null default 0',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_update_at' => '(update_at)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tChat}_owner" => "FOREIGN KEY (owner_user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tParticipant, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'chat_id' => 'int unsigned not null',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'chat_title' => 'varchar(50) null',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_update_at' => '(update_at)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tParticipant}_chat" => "FOREIGN KEY (chat_id) REFERENCES {$tChat} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tParticipant}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);

        $this->BDb->ddlTableDef($tHistory, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'chat_id' => 'int unsigned not null',
                'user_id' => 'int unsigned not null',
                'entry_type' => 'varchar(20) default "text"',
                'text' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'IDX_update_at' => '(update_at)',
            ],
            'CONSTRAINTS' => [
                "FK_{$tHistory}_chat" => "FOREIGN KEY (chat_id) REFERENCES {$tChat} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tHistory}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);

        $tUserStatus = $this->FCom_AdminChat_Model_UserStatus->table();

        $this->BDb->ddlTableDef($tUserStatus, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'message' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tUserStatus}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tChat = $this->FCom_AdminChat_Model_Chat->table();
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlTableDef($tChat, [
            'COLUMNS' => [
                'owner_user_id' => 'int unsigned not null after `status`',
            ],
            'CONSTRAINTS' => [
                "FK_{$tChat}_owner" => "FOREIGN KEY (owner_user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tHistory = $this->FCom_AdminChat_Model_History->table();
        $this->BDb->ddlTableDef($tHistory, [
            'COLUMNS' => [
                'entry_type' => 'varchar(20) default "text" after `user_id`',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tUserStatus = $this->FCom_AdminChat_Model_UserStatus->table();
        $tUser = $this->FCom_Admin_Model_User->table();

        $this->BDb->ddlTableDef($tUserStatus, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'message' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => [
                "FK_{$tUserStatus}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ],
        ]);
    }
    public function upgrade__0_1_3__0_1_4()
    {
        $tParticipant = $this->FCom_AdminChat_Model_Participant->table();
        $this->BDb->ddlTableDef($tParticipant, [
            'COLUMNS' => [
                'chat_title' => 'varchar(50) null after `status`',
            ],
        ]);
    }
}
