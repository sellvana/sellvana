<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Migrate
 *
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 */

class Sellvana_MultiSite_Migrate extends BClass
{
    public function install__0_1_2()
    {
        $tSite = $this->Sellvana_MultiSite_Model_Site->table();

        $this->BDb->ddlTableDef($tSite, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'name' => 'varchar(100) not null',
                'match_domains' => 'text',
                'default_theme' => 'varchar(100)',
                'layout_update' => 'text',
                'root_category_id' => 'int unsigned',
                'data_serialized' => 'text default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_name' => '(name)',
            ],
        ]);
    }
    public function upgrade__0_1_0__0_1_1()
    {
        $tSite = $this->Sellvana_MultiSite_Model_Site->table();
        $this->BDb->ddlTableDef($tSite, [
            BDb::COLUMNS => [
                'create_dt' => 'RENAME create_at datetime NOT NULL',
                'update_dt' => 'RENAME update_at datetime NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tSite = $this->Sellvana_MultiSite_Model_Site->table();
        $this->BDb->ddlTableDef($tSite, [
            BDb::COLUMNS => [
                'data_serialized' => 'text default null',
                'layout_update' => BDb::DROP,
                'mode_by_ip' => BDb::DROP,
                'meta_title' => BDb::DROP,
                'meta_description' => BDb::DROP,
                'meta_keywords' => BDb::DROP,
            ],
        ]);

    }
}
