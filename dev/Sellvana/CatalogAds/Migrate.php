<?php

/**
 * Class Sellvana_CatalogAds_Migrate
 *
 * @property Sellvana_CatalogAds_Model_Ad $Sellvana_CatalogAds_Model_Ad
 * @property Sellvana_CatalogAds_Model_AdCategory $Sellvana_CatalogAds_Model_AdCategory
 * @property Sellvana_CatalogAds_Model_AdTerm $Sellvana_CatalogAds_Model_AdTerm
 * @property Sellvana_Cms_Model_Block $Sellvana_Cms_Model_Block
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 */
class Sellvana_CatalogAds_Migrate extends BClass
{
    public function install__0_6_0_0()
    {
        $tAd = $this->Sellvana_CatalogAds_Model_Ad->table();
        $tAdCategory = $this->Sellvana_CatalogAds_Model_AdCategory->table();
        $tAdTerm = $this->Sellvana_CatalogAds_Model_AdTerm->table();
        $tCmsBlock = $this->Sellvana_Cms_Model_Block->table();
        $tCategory = $this->Sellvana_Catalog_Model_Category->table();

        $this->BDb->ddlTableDef($tAd, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'name' => 'varchar(255) not null',
                'description' => 'text',
                'priority' => 'tinyint unsigned not null',
                'grid_position' => 'tinyint unsigned not null',
                'grid_cms_block_id' => 'int unsigned default null',
                'grid_tile_contents' => 'text',
                'list_position' => 'tinyint unsigned not null',
                'list_cms_block_id' => 'int unsigned default null',
                'list_tile_contents' => 'text',
                'create_at' => 'datetime default null',
                'update_at' => 'datetime default null',
                'data_serialized' => 'text default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'grid_cms_block' => ['grid_cms_block_id', $tCmsBlock],
                'list_cms_block' => ['list_cms_block_id', $tCmsBlock],
            ],
        ]);

        $this->BDb->ddlTableDef($tAdCategory, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'ad_id' => 'int unsigned not null',
                'category_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_ad_category' => 'UNIQUE (ad_id, category_id)',
            ],
            BDb::CONSTRAINTS => [
                'ad' => ['ad_id', $tAd],
                'category' => ['category_id', $tCategory],
            ],
        ]);

        $this->BDb->ddlTableDef($tAdTerm, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'ad_id' => 'int unsigned not null',
                'term' => 'varchar(50) not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_ad_term' => 'UNIQUE (ad_id, term)',
            ],
            BDb::CONSTRAINTS => [
                'ad' => ['ad_id', $tAd],
            ],
        ]);
    }
}