<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_MultiSite_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'multisite';
    protected $_modelClass = 'Sellvana_MultiSite_Model_Site';
    protected $_gridTitle = 'Multi Sites';
    protected $_recordName = 'Site';
    protected $_mainTableAlias = 's';
    protected $_permission = 'multi_site';
    protected $_formViewPrefix = 'multisite/sites-form/';
    protected $_navPath = 'system/multisite';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 's.id'],
            ['name' => 'name', 'label' => 'Site Name', 'index' => 's.name'],
            ['name' => 'match_domains', 'label' => 'Match Domains', 'index' => 's.match_domains'],
            ['name' => 'default_theme', 'label' => 'Default Theme', 'index' => 's.default_theme'],
            ['name' => 'mode_by_ip', 'label' => 'Mode by IP', 'index' => 's.mode_by_ip'],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 's.create_at', 'formatter' => 'date'],
            ['name' => 'update_at', 'label' => 'Updated', 'index' => 's.update_at', 'formatter' => 'date'],
        ];
        $config['actions'] = [
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'name', 'type' => 'text'],
            ['field' => 'match_domains', 'type' => 'text'],
            ['field' => 'default_theme', 'type' => 'text'],
            ['field' => 'mode_by_ip', 'type' => 'text'],
        ];
        return $config;
    }
}
