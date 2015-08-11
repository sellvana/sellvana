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
            ['name' => 'create_at', 'index' => 'o.create_at'],
            ['name' => 'product_sku', 'index' => 'oi.product_sku'],
            ['name' => 'unique_id', 'index' => 'unique_id'],
            ['name' => 'qty_ordered', 'index' => 'oi.qty_ordered'],
            ['name' => 'price', 'index' => 'oi.price'],
            ['name' => 'row_price', 'index' => 'row_price'],
        ];
        $config['filters'] = [
            ['field' => 'product_sku', 'type' => 'text'],
            ['field' => 'create_at', 'type' => 'date-range'],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'create_at' => 'Order Date',
            'product_sku' => 'SKU',
            'unique_id' => 'Order #',
            'qty_ordered' => 'Qty of ordered',
            'price' => 'Unit Price',
            'row_price' => 'Row Total',
        ];
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