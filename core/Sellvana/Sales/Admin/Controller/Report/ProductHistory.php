<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
            ['name' => 'period', 'index' => 'period', 'label' => 'Period', 'width' => 70],
            ['name' => 'product_sku', 'index' => 'product_sku', 'label' => 'Product SKU'],
            ['name' => 'product_name', 'index' => 'product_name', 'label' => 'Product Name'],
            ['name' => 'qty_sold', 'index' => 'qty_sold', 'label' => 'Qty'],
            ['name' => 'period_subtotal', 'index' => 'period_subtotal', 'label' => '$Subtotal'],
            ['name' => 'period_discount', 'index' => 'period_discount', 'label' => '$Discount'],
            ['name' => 'period_total', 'index' => 'period_total', 'label' => '$Total'],
            ['name' => 'period_received', 'index' => 'period_received', 'label' => '$Received'],
            ['name' => 'period_refunded', 'index' => 'period_refunded', 'label' => '$Refunded'],
            ['name' => 'avg_price', 'index' => 'avg_price', 'label' => 'Avg Sell Price'],
            ['name' => 'max_price', 'index' => 'max_price', 'label' => 'Max Sell Price'],
            ['name' => 'min_price', 'index' => 'min_price', 'label' => 'Min Sell Price'],

            ['name' => 'period_type', 'label' => 'Period', 'options' => $this->_periodTypes, 'hidden' => true],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'o.create_at', 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'period_type', 'type' => 'multiselect', 'callback' => 'periodTypeCallback'],
        ];

        return $config;
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