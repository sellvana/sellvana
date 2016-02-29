<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Report_ProductHistory
 */
class Sellvana_Sales_Admin_Controller_Report_ProductHistory extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order_Item';
    protected $_mainTableAlias = 'oi';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/product_history';
    protected $_gridHref = 'sales/report/product_history';
    protected $_gridTitle = 'Product History';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['name' => 'period', 'index' => 'period', 'width' => 70],
            ['name' => 'product_sku', 'index' => 'product_sku'],
            ['name' => 'product_name', 'index' => 'product_name'],
            ['name' => 'qty_sold', 'index' => 'qty_sold'],
            ['name' => 'period_subtotal', 'index' => 'period_subtotal', 'cell' => 'currency'],
            ['name' => 'period_discount', 'index' => 'period_discount', 'cell' => 'currency'],
            ['name' => 'period_total', 'index' => 'period_total', 'cell' => 'currency'],
            ['name' => 'period_received', 'index' => 'period_received', 'cell' => 'currency'],
            ['name' => 'period_refunded', 'index' => 'period_refunded', 'cell' => 'currency'],
            ['name' => 'avg_price', 'index' => 'avg_price', 'cell' => 'currency'],
            ['name' => 'max_price', 'index' => 'max_price', 'cell' => 'currency'],
            ['name' => 'min_price', 'index' => 'min_price', 'cell' => 'currency'],

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
            'product_sku' => 'Product SKU',
            'product_name' => 'Product Name',
            'qty_sold' => 'Qty',
            'period_subtotal' => 'Subtotal',
            'period_discount' => 'Discount',
            'period_total' => 'Total',
            'period_received' => 'Received',
            'period_refunded' => 'Refunded',
            'avg_price' => 'Avg Sell Price',
            'max_price' => 'Max Sell Price',
            'min_price' => 'Min Sell Price',
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
            ->select_expr('SUM(oi.row_total)', 'period_subtotal')
            ->select_expr('SUM(oi.row_discount)', 'period_discount')
            ->select_expr('SUM(oi.row_total + oi.row_tax - oi.row_discount)', 'period_total')
            ->select_expr('IFNULL(SUM(o.amount_paid), 0)', 'period_received')
            ->select_expr('IFNULL(SUM(o.amount_refunded), 0)', 'period_refunded')
            ->select_expr('SUM(oi.price * oi.qty_ordered) / SUM(oi.qty_ordered)', 'avg_price')
            ->select_expr('MAX(oi.price)', 'max_price')
            ->select_expr('MIN(oi.price)', 'min_price')
            ->group_by('oi.product_id');
    }
}