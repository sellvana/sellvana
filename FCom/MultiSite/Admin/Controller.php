<?php

class FCom_MultiSite_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{

    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'multisite';
    protected $_modelClass = 'FCom_MultiSite_Model_Site';
    protected $_gridTitle = 'Multi Sites';
    protected $_recordName = 'Site';
    protected $_mainTableAlias = 's';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array_replace_recursive($config['grid']['columns'], array(
            'id' => array('index'=>'s.id'),
            'name' => array('label'=>'Site Name', 'index'=>'s.name'),
            'match_domains' => array('label'=>'Match Domains', 'index'=>'s.match_domains'),
            'default_theme' => array('label'=>'Default Theme', 'index'=>'s.default_theme'),
            'mode_by_ip' => array('label'=>'Mode by IP', 'index'=>'s.mode_by_ip'),
            'create_at' => array('label'=>'Created', 'index'=>'s.create_at', 'formatter'=>'date'),
            'update_at' => array('label'=>'Updated', 'index'=>'s.update_at', 'formatter'=>'date'),
        ));
        $config['custom']['dblClickHref'] = BApp::href('multisite/form/?id=');
        return $config;
    }
}
