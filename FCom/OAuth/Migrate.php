<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_OAuth_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tConsumerToken = $this->FCom_OAuth_Model_ConsumerToken->table();

        $this->BDb->ddlTableDef($tConsumerToken, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'provider' => 'varchar(50) not null',
                'token' => 'varchar(255) binary not null',
                'token_secret' => 'varchar(255) binary null',
                'customer_id' => 'int unsigned null',
                'admin_id' => 'int unsigned null',
                'data_serialized' => "text",
                'create_at' => 'timestamp not null default current_timestamp',
                'expire_at' => "datetime not null default '9999-12-31'",
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'unq_provider_token' => 'UNIQUE (provider, token)',
                'idx_expire' => '(expire_at)',
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tConsumerToken = $this->FCom_OAuth_Model_ConsumerToken->table();

        $this->BDb->ddlTableDef($tConsumerToken, [
            BDb::COLUMNS => [
                'token' => 'varchar(255) binary not null',
                'token_secret' => 'varchar(255) binary null',
            ],
        ]);
    }
}
