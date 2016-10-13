<?php

/**
 * Class Sellvana_CatalogAds_Admin_Controller_Ads
 *
 * @property Sellvana_Cms_Model_Block $Sellvana_Cms_Model_Block
 */
class Sellvana_CatalogAds_Admin_Controller_Ads extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;

    protected $_modelClass = 'Sellvana_CatalogAds_Model_Ad';
    protected $_recordName = 'Catalog Ad';
    protected $_mainTableAlias = 'a';
    protected $_permission = 'catalog/ads';
    protected $_navPath = 'catalog/ads';

    protected $_gridHref = 'catalog/ads';
    protected $_gridTitle = 'Catalog Ads';

    protected $_formViewPrefix = 'catalog/ads-form/';
    protected $_formTitleField = 'name';
    
    public function gridConfig()
    {
        $config = parent::gridConfig();

        $cmsBlocks = $this->Sellvana_Cms_Model_Block->getAllBlocksAsOptions();
        
        $config['orm'] = $this->gridOrm()
            ->left_outer_join('Sellvana_Cms_Model_Block', ['bg.id', '=', 'a.grid_cms_block_id'], 'bg')
            ->left_outer_join('Sellvana_Cms_Model_Block', ['bl.id', '=', 'a.list_cms_block_id'], 'bl')
            ->select(['bg.handle', 'bl.handle']);
        
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
            ]],
            ['name' => 'name', 'label' => 'Ad Name'],
            ['name' => 'priority', 'label' => 'Priority'],
            ['name' => 'grid_position', 'label' => 'Grid Position'],
            ['name' => 'grid_cms_block_id', 'label' => 'Grid CMS Block', 'options' => $cmsBlocks],
            ['name' => 'list_position', 'label' => 'Grid Position'],
            ['name' => 'list_cms_block_id', 'label' => 'Grid CMS Block', 'options' => $cmsBlocks],
            ['name' => 'create_at', 'label' => 'Created', 'cell' => 'datetime'],
            ['name' => 'update_at', 'label' => 'Created', 'cell' => 'datetime'],
        ];

        $config['filters'] = [
            ['field' => 'name', 'type' => 'text'],
            ['field' => 'priority', 'type' => 'number-range'],
            ['field' => 'grid_position', 'type' => 'number-range'],
            ['field' => 'grid_cms_block_id', 'type' => 'multiselect'],
            ['field' => 'list_position', 'type' => 'number-range'],
            ['field' => 'list_cms_block_id', 'type' => 'multiselect'],
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'update_at', 'type' => 'date-range'],
        ];
        
        return $config;
    }
}