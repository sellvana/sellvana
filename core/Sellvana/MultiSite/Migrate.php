<?php

/**
 * Class Sellvana_MultiSite_Migrate
 *
 * @property Sellvana_MultiSite_Model_Site $Sellvana_MultiSite_Model_Site
 * @property Sellvana_MultiSite_Model_SiteUser $Sellvana_MultiSite_Model_SiteUser
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
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
    
    public function upgrade__0_5_0_0__0_5_1_0()
    {
        $tSite = $this->Sellvana_MultiSite_Model_Site->table();
        $this->BDb->ddlTableDef($tSite, [
            BDb::COLUMNS => [
                'home_url' => 'varchar(255) default null',
            ],
        ]);
    }

    public function upgrade__0_5_1_0__0_5_2_0()
    {
        $tSite = $this->Sellvana_MultiSite_Model_Site->table();
        $tUser = $this->FCom_Admin_Model_User->table();
        $tRole = $this->FCom_Admin_Model_Role->table();
        $tSiteUser = $this->Sellvana_MultiSite_Model_SiteUser->table();

        $this->BDb->ddlTableDef($tSiteUser, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'site_id' => 'int unsigned not null',
                'user_id' => 'int unsigned not null',
                'role_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_site_user_role' => 'UNIQUE (site_id, user_id, role_id)',
            ],
            BDb::CONSTRAINTS => [
                'site' => ['site_id', $tSite],
                'user' => ['user_id', $tUser],
                'role' => ['role_id', $tRole],
            ],
        ]);
    }
}
