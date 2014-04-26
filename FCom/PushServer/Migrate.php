<?php

class FCom_PushServer_Migrate extends BClass
{
    public function install__0_1_3()
    {
        $tChannel = FCom_PushServer_Model_Channel::table();
        $tClient = FCom_PushServer_Model_Client::table();
        $tMessage = FCom_PushServer_Model_Message::table();
        $tSubscriber = FCom_PushServer_Model_Subscriber::table();

        BDb::ddlTableDef($tChannel, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'channel_name' => 'varchar(255) not null',
                'channel_out' => 'varchar(100)',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_channel_name' => '(channel_name)',
                'IDX_update_at' => '(update_at)',
            ),
        ));

        BDb::ddlTableDef($tClient, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'session_id' => 'varchar(100)',
                'status' => 'varchar(10)',
                'admin_user_id' => 'int unsigned null',
                'customer_id' => 'int unsigned null',
                'remote_ip' => 'varchar(20)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
                'data_serialized' => 'text',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_session_id' => '(session_id)',
                'IDX_update_at' => '(update_at)',
            ),
        ));

        BDb::ddlTableDef($tSubscriber, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'channel_id' => 'int unsigned not null',
                'client_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_update_at' => '(update_at)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tSubscriber}_channel" => "FOREIGN KEY (channel_id) REFERENCES {$tChannel} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tSubscriber}_client" => "FOREIGN KEY (client_id) REFERENCES {$tClient} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));

        BDb::ddlTableDef($tMessage, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'seq' => 'varchar(30)',
                'channel_id' => 'int unsigned null',
                'subscriber_id' => 'int unsigned not null',
                'client_id' => 'int unsigned not null',
                'window_name' => 'varchar(30) null',
                'conn_id' => 'int unsigned null',
                'status' => 'varchar(20)',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_update_at' => '(update_at)',
                'IDX_client_window_status' => '(client_id, window_name, status)'
            ),
            'CONSTRAINTS' => array(
                "FK_{$tMessage}_channel" => "FOREIGN KEY (channel_id) REFERENCES {$tChannel} (id) ON UPDATE CASCADE ON DELETE SET NULL",
                "FK_{$tMessage}_subscriber" => "FOREIGN KEY (subscriber_id) REFERENCES {$tSubscriber} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tMessage}_client" => "FOREIGN KEY (client_id) REFERENCES {$tClient} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tChannel = FCom_PushServer_Model_Channel::table();
        $tClient = FCom_PushServer_Model_Client::table();
        $tMessage = FCom_PushServer_Model_Message::table();
        $tSubscriber = FCom_PushServer_Model_Subscriber::table();

        BDb::ddlTableDef($tClient, array(
            'COLUMNS' => array(
                'handover' => 'DROP',
            ),
        ));

        BDb::run("DROP TABLE IF EXISTS {$tMessage}");

        BDb::ddlTableDef($tMessage, array(
            'COLUMNS' => array(
                'id' => 'int unsigned not null auto_increment',
                'seq' => 'varchar(30)',
                'channel_id' => 'int unsigned null',
                'subscriber_id' => 'int unsigned not null',
                'client_id' => 'int unsigned not null',
                'page_id' => 'varchar(30) null',
                'conn_id' => 'int unsigned null',
                'status' => 'varchar(20)',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ),
            'PRIMARY' => '(id)',
            'KEYS' => array(
                'IDX_update_at' => '(update_at)',
            ),
            'CONSTRAINTS' => array(
                "FK_{$tMessage}_channel" => "FOREIGN KEY (channel_id) REFERENCES {$tChannel} (id) ON UPDATE CASCADE ON DELETE SET NULL",
                "FK_{$tMessage}_subscriber" => "FOREIGN KEY (subscriber_id) REFERENCES {$tSubscriber} (id) ON UPDATE CASCADE ON DELETE CASCADE",
                "FK_{$tMessage}_client" => "FOREIGN KEY (client_id) REFERENCES {$tClient} (id) ON UPDATE CASCADE ON DELETE CASCADE",
            ),
        ));
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tMessage = FCom_PushServer_Model_Message::table();
        BDb::ddlTableDef($tMessage, array(
            'COLUMNS' => array(
                'page_id' => 'RENAME window_id varchar(30) null',
            ),
            'KEYS' => array(
                'IDX_client_window_status' => '(client_id, window_id, status)',
            ),
        ));
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tMessage = FCom_PushServer_Model_Message::table();
        BDb::ddlTableDef($tMessage, array(
            'COLUMNS' => array(
                'window_id' => 'RENAME window_name varchar(30) null',
            ),
            'KEYS' => array(
                'IDX_client_window_status' => '(client_id, window_name, status)',
            ),
        ));
    }

    // Support for IPv6:
    // 39 nominal and 6 extra for embedded ipv4 as ipv6.
    public function upgrade__0_1_3__0_1_4()
    {
        $tClient = FCom_PushServer_Model_Client::table();
        BDb::ddlTableDef($tClient, array(
            'COLUMNS' => array(
                'remote_ip' => 'remote_ip varchar(45)',
            )
        ));
    }
}
