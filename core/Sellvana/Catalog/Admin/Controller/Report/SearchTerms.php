<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_Report_SearchTerms
 */
class Sellvana_Catalog_Admin_Controller_Report_SearchTerms extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Catalog_Model_SearchHistory';
    protected $_mainTableAlias = 'sh';
    protected $_permission = 'catalog/reports';
    protected $_navPath = 'reports/catalog/search_terms';
    protected $_gridHref = 'catalog/report/search_terms';
    protected $_gridTitle = 'Search Terms';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'query', 'index' => 'query', 'label' => 'Search String'],
            ['name' => 'num_searches', 'index' => 'num_searches', 'label' => '# of searches'],
            ['name' => 'num_products_found_last', 'index' => 'num_products_found_last', 'label' => '# of Results'],
            ['name' => 'first_at', 'index' => 'first_at', 'label' => 'First Search', 'hidden' => true],
            ['name' => 'last_at', 'index' => 'last_at', 'label' => 'Last Search', 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'first_at', 'type' => 'date-range'],
            ['field' => 'last_at', 'type' => 'date-range'],
        ];

        return $config;
    }
}