<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PushServer_Migrate
 *
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property FCom_PushServer_Model_Message $FCom_PushServer_Model_Message
 * @property FCom_PushServer_Model_Subscriber $FCom_PushServer_Model_Subscriber
 */

class FCom_PushServer_Migrate extends BClass
{
    public function install__0_1_4()
    {
        $tChannel = $this->FCom_PushServer_Model_Channel->table();
        $tClient = $this->FCom_PushServer_Model_Client->table();
        $tMessage = $this->FCom_PushServer_Model_Message->table();
        $tSubscriber = $this->FCom_PushServer_Model_Subscriber->table();

        $this->BDb->ddlTableDef($tChannel, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'channel_name' => 'varchar(255) not null',
                'channel_out' => 'varchar(100)',
                'data_serialized' => 'text',
                #'data_serialized' => 'varchar(255)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_channel_name' => '(channel_name)',
                'IDX_update_at' => '(update_at)',
                #'IDX_channel_name' => '(channel_name) USING BTREE',
                #'IDX_update_at' => '(update_at) USING BTREE',
            ],
            BDb::OPTIONS => [
                #'engine' => 'MEMORY',
            ],
        ]);

        $this->BDb->ddlTableDef($tClient, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'session_id' => 'varchar(100)',
                'status' => 'varchar(10)',
                'admin_user_id' => 'int unsigned null',
                'customer_id' => 'int unsigned null',
                'remote_ip' => 'varchar(45)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
                'data_serialized' => 'text',
                #'data_serialized' => 'varchar(255)',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_session_id' => '(session_id)',
                'IDX_update_at' => '(update_at)',
                #'IDX_session_id' => '(session_id) USING BTREE',
                #'IDX_update_at' => '(update_at) USING BTREE',
            ],
            BDb::OPTIONS => [
                #'engine' => 'MEMORY',
            ],
        ]);

        $this->BDb->ddlTableDef($tSubscriber, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'channel_id' => 'int unsigned not null',
                'client_id' => 'int unsigned not null',
                'status' => 'varchar(20)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_update_at' => '(update_at)',
                #'IDX_update_at' => '(update_at) USING BTREE',
            ],
            BDb::CONSTRAINTS => [
                'channel' => ['channel_id', $tChannel],
                'client' => ['client_id', $tClient],
            ],
            BDb::OPTIONS => [
                #'engine' => 'MEMORY',
            ],
        ]);

        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'seq' => 'varchar(30)',
                'channel_id' => 'int unsigned null',
                'subscriber_id' => 'int unsigned not null',
                'client_id' => 'int unsigned not null',
                'window_name' => 'varchar(30) null',
                'conn_id' => 'int unsigned null',
                'status' => 'varchar(20)',
                'data_serialized' => 'text',
                #'data_serialized' => 'varchar(10000)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_update_at' => '(update_at)',
                'IDX_client_window_status' => '(client_id, window_name, status)',
                #'IDX_update_at' => '(update_at) USING BTREE',
                #'IDX_client_window_status' => '(client_id, window_name, status) USING BTREE'
            ],
            BDb::CONSTRAINTS => [
                'channel' => ['channel_id', $tChannel, 'id', 'CASCADE', 'SET NULL'],
                'subscriber' => ['subscriber_id', $tSubscriber],
                'client' => ['client_id', $tClient],
            ],
            BDb::OPTIONS => [
                #'engine' => 'MEMORY',
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tChannel = $this->FCom_PushServer_Model_Channel->table();
        $tClient = $this->FCom_PushServer_Model_Client->table();
        $tMessage = $this->FCom_PushServer_Model_Message->table();
        $tSubscriber = $this->FCom_PushServer_Model_Subscriber->table();

        $this->BDb->ddlTableDef($tClient, [
            BDb::COLUMNS => [
                'handover' => BDb::DROP,
            ],
        ]);

        $this->BDb->run("DROP TABLE IF EXISTS {$tMessage}");

        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
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
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_update_at' => '(update_at)',
            ],
            BDb::CONSTRAINTS => [
                'channel' => ['channel_id', $tChannel, 'id', 'CASCADE', 'SET NULL'],
                'subscriber' => ['subscriber_id', $tSubscriber],
                'client' => ['client_id', $tClient],
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tMessage = $this->FCom_PushServer_Model_Message->table();
        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'page_id' => 'RENAME window_id varchar(30) null',
            ],
            BDb::KEYS => [
                'IDX_client_window_status' => '(client_id, window_id, status)',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tMessage = $this->FCom_PushServer_Model_Message->table();
        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'window_id' => 'RENAME window_name varchar(30) null',
            ],
            BDb::KEYS => [
                'IDX_client_window_status' => '(client_id, window_name, status)',
            ],
        ]);
    }

    // Support for IPv6:
    // 39 nominal and 6 extra for embedded ipv4 as ipv6.
    public function upgrade__0_1_3__0_1_4()
    {
        $tClient = $this->FCom_PushServer_Model_Client->table();
        $this->BDb->ddlTableDef($tClient, [
            BDb::COLUMNS => [
                'remote_ip' => 'varchar(45)',
            ]
        ]);
    }
    /*
    public function upgrade__0_1_4__0_1_5()
    {
        $tChannel = $this->FCom_PushServer_Model_Channel->table();
        $tClient = $this->FCom_PushServer_Model_Client->table();
        $tMessage = $this->FCom_PushServer_Model_Message->table();
        $tSubscriber = $this->FCom_PushServer_Model_Subscriber->table();

        $this->BDb->run("
          DROP TABLE IF EXISTS {$tMessage};
          DROP TABLE IF EXISTS {$tSubscriber};
          DROP TABLE IF EXISTS {$tClient};
          DROP TABLE IF EXISTS {$tChannel};
        ");

        $this->BDb->ddlClearCache();

        $this->install__0_1_5();
    }
    */
}
