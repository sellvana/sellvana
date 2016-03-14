<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Report_ProductPerformance
 */
class Sellvana_Sales_Admin_Controller_Report_ProductPerformance extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order_Item';
    protected $_mainTableAlias = 'oi';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/product_performance';
    protected $_gridHref = 'sales/report/product_performance';
    protected $_gridTitle = 'Product Performance';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'period', 'index' => 'period', 'width' => 70],
            ['name' => 'product_sku', 'index' => 'product_sku', 'width' => 70],
            ['name' => 'product_name', 'index' => 'product_name'],
            ['name' => 'qty_sold', 'index' => 'qty_sold'],
            ['name' => 'row_total_amount', 'index' => 'row_total_amount', 'cell' => 'currency'],

            ['name' => 'period_type', 'options' => $this->_periodTypes, 'hidden' => true],
            ['name' => 'create_at', 'index' => 'o.create_at', 'hidden' => true, 'cell' => 'datetime'],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'period_type', 'type' => 'multiselect', 'callback' => 'periodTypeCallback'],
        ];

        return $config;
    }


    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'period' => 'Period',
            'product_sku' => 'Inventory SKU',
            'product_name' => 'Inventory SKU Name',
            'qty_sold' => 'Qty Sold',
            'row_total_amount' => 'Total After Discounts',
            'period_type' => 'Period',
            'create_at' => 'Created',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->join('Sellvana_Sales_Model_Order', 'oi.order_id = o.id', 'o')
            ->select_expr('SUM(oi.qty_ordered)', 'qty_sold')
            ->select_expr('SUM(oi.row_total - oi.row_discount)', 'row_total_amount')
            ->group_by('oi.product_id');
    }
}