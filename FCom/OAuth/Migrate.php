<?php

class FCom_OAuth_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $tConsumerToken = FCom_OAuth_Model_ConsumerToken::table();

        BDb::ddlTableDef($tConsumerToken, [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'provider' => 'varchar(50) not null',
                'token' => 'varchar(64) binary not null',
                'token_secret' => 'varchar(64) binary not null',
                'customer_id' => 'int unsigned null',
                'admin_id' => 'int unsigned null',
                'data_serialized' => "text",
                'create_at' => 'timestamp not null default current_timestamp',
                'expire_at' => "datetime not null default '9999-12-31'",
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'unq_provider_token' => 'UNIQUE (provider, token)',
                'idx_expire' => '(expire_at)',
            ],
        ]);
    }
}
