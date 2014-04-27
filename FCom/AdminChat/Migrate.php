<?php

class FCom_AdminChat_Migrate extends BClass
{
    public function install__0_1_4()
    {
        $tChat = FCom_AdminChat_Model_Chat::table();
        $tParticipant = FCom_AdminChat_Model_Participant::table();
        $tHistory = FCom_AdminChat_Model_History::table();
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlTableDef( $tChat, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'status' => 'varchar(20)',
                'owner_user_id' => 'int unsigned not null',
                'num_participants' => 'smallint unsigned not null default 0',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_update_at' => '(update_at)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tChat}_owner" => "FOREIGN KEY (owner_user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ) );

        BDb::ddlTableDef( $tParticipant, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'chat_id' => 'int unsigned not null',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'chat_title' => 'varchar(50) null',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_update_at' => '(update_at)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tParticipant}_chat" => "FOREIGN KEY (chat_id) REFERENCES {$tChat} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tParticipant}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ) );

        BDb::ddlTableDef( $tHistory, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'chat_id' => 'int unsigned not null',
                'user_id' => 'int unsigned not null',
                'entry_type' => 'varchar(20) default "text"',
                'text' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_update_at' => '(update_at)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tHistory}_chat" => "FOREIGN KEY (chat_id) REFERENCES {$tChat} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tHistory}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ) );

        $tUserStatus = FCom_AdminChat_Model_UserStatus::table();

        BDb::ddlTableDef( $tUserStatus, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'message' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => array(
                "FK_{$tUserStatus}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ) );
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tChat = FCom_AdminChat_Model_Chat::table();
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlTableDef( $tChat, array(
            'COLUMNS' => array(
                'owner_user_id' => 'int unsigned not null after `status`',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tChat}_owner" => "FOREIGN KEY (owner_user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ) );
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tHistory = FCom_AdminChat_Model_History::table();
        BDb::ddlTableDef( $tHistory, array(
            'COLUMNS' => array(
                'entry_type' => 'varchar(20) default "text" after `user_id`',
            ),
        ) );
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tUserStatus = FCom_AdminChat_Model_UserStatus::table();
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlTableDef( $tUserStatus, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'user_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'message' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'CONSTRAINTS' => array(
                "FK_{$tUserStatus}_user" => "FOREIGN KEY (user_id) REFERENCES {$tUser} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ) );
    }
    public function upgrade__0_1_3__0_1_4()
    {
        $tParticipant = FCom_AdminChat_Model_Participant::table();
        BDb::ddlTableDef( $tParticipant, array(
            'COLUMNS' => array(
                'chat_title' => 'varchar(50) null after `status`',
            ),
        ) );
    }
}
