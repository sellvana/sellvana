<?php

/**
 * Class Sellvana_Customer_Admin_Controller_Report_Country
 */
class Sellvana_Customer_Admin_Controller_Report_Country extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Customer_Model_Customer';
    protected $_mainTableAlias = 'c';
    protected $_permission = 'customer/reports';
    protected $_navPath = 'reports/customer/country';
    protected $_gridHref = 'customer/report/country';
    protected $_gridTitle = 'Users by Country';


    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'country', 'index' => 'ca.country', 'width' => 100, 'options' => $this->BLocale->getAvailableCountries('name')],
            ['name' => 'region', 'index' => 'ca.region'],
            ['name' => 'city', 'index' => 'ca.city'],
            ['name' => 'customer_count', 'index' => 'customer_count'],
            ['name' => 'customer_with_order_count', 'index' => 'customer_with_order_count'],
            ['name' => 'order_count', 'index' => 'order_count'],
            ['name' => 'item_count', 'index' => 'item_count'],
            ['name' => 'total_amount', 'index' => 'total_amount', 'cell' => 'currency'],
            ['name' => 'create_at', 'index' => 'o.create_at', 'hidden' => true, 'cell' => 'datetime'],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'country', 'type' => 'multiselect'],
            ['field' => 'region', 'type' => 'text'],
            ['field' => 'city', 'type' => 'text'],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'country' => 'Country',
            'region' => 'State',
            'city' => 'City',
            'customer_count' => '# of Customers',
            'customer_with_order_count' => '# of Customers who Ordered',
            'order_count' => '# of Orders',
            'item_count' => '# of Items',
            'total_amount' => 'Total Sales',
            'create_at' => 'Created'
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('Sellvana_Customer_Model_Address', 'ca.customer_id = c.id AND c.default_shipping_id = ca.id', 'ca')
            ->left_outer_join('Sellvana_Sales_Model_Order', 'o.customer_id = c.id', 'o')
            ->select_expr('COUNT(DISTINCT c.id)', 'customer_count')
            ->select_expr('COUNT(DISTINCT o.customer_id)', 'customer_with_order_count')
            ->select_expr('COUNT(o.id)', 'order_count')
            ->select_expr('IFNULL(SUM(o.item_qty), 0)', 'item_count')
            ->select_expr('IFNULL(SUM(o.grand_total), 0)', 'total_amount')
            ->select(['country' => 'ca.country', 'region' => 'ca.region', 'city' => 'ca.city'])
            ->group_by('ca.country')
            ->group_by('ca.region')
            ->group_by('ca.city');
    }
}