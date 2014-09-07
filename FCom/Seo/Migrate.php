<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Seo_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $this->BDb->ddlTableDef($this->FCom_Seo_Model_UrlAlias->table(), [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'request_url' => 'varchar(100)',
                'target_url' => 'varchar(100)',
                'is_active' => 'tinyint',
                'is_regexp' => 'tinyint',
                'redirect_type' => 'varchar(10)',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_request_url' => 'UNIQUE (is_active, is_regexp, request_url)',
            ],
        ]);

        $this->BDb->ddlTableDef($this->FCom_Seo_Model_Sitemap->table(), [
            'COLUMNS' => [
                'id' => 'int unsigned not null auto_increment',
                'name' => 'varchar(100)',
                'url_key' => 'varchar(50)',
                'data_json' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            'PRIMARY' => '(id)',
            'KEYS' => [
                'UNQ_url_key' => 'UNIQUE (url_key)',
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $table = $this->FCom_Seo_Model_UrlAlias->table();
        $this->BDb->ddlTableDef($table, [
            'COLUMNS' => [
                  'create_dt'      => 'RENAME create_at datetime',
                  'update_dt'      => 'RENAME update_at datetime',
            ]
        ]);
        $table = $this->FCom_Seo_Model_Sitemap->table();
        $this->BDb->ddlTableDef($table, [
            'COLUMNS' => [
                  'create_dt'      => 'RENAME create_at datetime',
                  'update_dt'      => 'RENAME update_at datetime',
            ],
        ]);
    }
}
