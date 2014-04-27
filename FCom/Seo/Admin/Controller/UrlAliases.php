<?php

class FCom_Seo_Admin_Controller_UrlAliases extends FCom_Admin_Controller_Abstract_GridForm
{

    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'urlaliases';
    protected $_modelClass = 'FCom_Seo_Model_UrlAlias';
    protected $_gridTitle = 'URL Aliases';
    protected $_recordName = 'URL Alias';
    protected $_mainTableAlias = 'a';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config[ 'columns' ] = array_replace_recursive( $config[ 'columns' ], array(
            'id' => array( 'index' => 'a.id' ),
            'request_url' => array( 'label' => 'Request URL', 'index' => 'a.request_url' ),
            'target_url' => array( 'label' => 'Target URL', 'index' => 'a.target_url' ),
            'match_domains' => array( 'label' => 'Match Domains', 'index' => 'a.match_domains' ),
            'default_theme' => array( 'label' => 'Default Theme', 'index' => 'a.default_theme' ),
            'mode_by_ip' => array( 'label' => 'Mode by IP', 'index' => 'a.mode_by_ip' ),
            'create_at' => array( 'label' => 'Created', 'index' => 'a.create_at', 'formatter' => 'date' ),
            'update_at' => array( 'label' => 'Updated', 'index' => 'a.update_at', 'formatter' => 'date' ),
        ) );
        $config[ 'custom' ][ 'dblClickHref' ] = BApp::href( 'multisite/form/?id=' );
        return $config;
    }
}
