<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Admin_Controller_Report_ProductOrdersHistory
 */
class Sellvana_Catalog_Admin_Controller_Report_ProductOrdersHistory extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order_Item';
    protected $_mainTableAlias = 'oi';
    protected $_permission = 'catalog/reports';
    protected $_navPath = 'reports/catalog/product_orders_history';
    protected $_gridHref = 'catalog/report/product_orders_history';
    protected $_gridTitle = 'Product Orders History';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'create_at', 'index' => 'o.create_at', 'label' => 'Order Date'],
            ['name' => 'product_sku', 'index' => 'oi.product_sku', 'label' => 'SKU'],
            ['name' => 'unique_id', 'index' => 'unique_id', 'label' => 'Order #'],
            ['name' => 'qty_ordered', 'index' => 'oi.qty_ordered', 'label' => 'Qty of ordered'],
            ['name' => 'price', 'index' => 'oi.price', 'label' => 'Unit Price'],
            ['name' => 'row_price', 'index' => 'row_price', 'label' => 'Row Total'],
        ];
        $config['filters'] = [
            ['field' => 'product_sku', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
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
            ->select_expr('oi.qty_ordered * oi.price', 'row_price')
            ->select(['create_at' => 'o.create_at', 'unique_id' => 'o.unique_id'])
            ->group_by('o.id')
            ->group_by('oi.product_id');
    }
}