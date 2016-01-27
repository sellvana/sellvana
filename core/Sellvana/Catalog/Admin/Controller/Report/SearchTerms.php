<?php

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
    protected $_gridHref = 'catalog/report/inventory/search_terms';
    protected $_gridTitle = 'Search Terms';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'query', 'index' => 'query'],
            ['name' => 'num_searches', 'index' => 'num_searches'],
            ['name' => 'num_products_found_last', 'index' => 'num_products_found_last'],
            ['name' => 'first_at', 'index' => 'first_at', 'hidden' => true],
            ['name' => 'last_at', 'index' => 'last_at', 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'first_at', 'type' => 'date-range'],
            ['field' => 'last_at', 'type' => 'date-range'],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'query' => 'Search String',
            'num_searches' => '# of searches',
            'num_products_found_last' => '# of Results',
            'first_at' => 'First Search',
            'last_at' => 'Last Search',
        ];
    }


}