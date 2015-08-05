<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_Report_Hour
 */
class Sellvana_Sales_Admin_Controller_Report_Hour extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/hour';
    protected $_gridHref = 'sales/report/hour';
    protected $_gridTitle = 'Hour';


    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['name' => 'hour', 'index' => 'hour', 'label' => 'Hour', 'width' => 50],
            ['name' => 'pc_total_amount', 'index' => 'pc_total_amount', 'label' => '% of Total'],
            ['name' => 'order_count', 'index' => 'order_count', 'label' => '# of Orders'],
            ['name' => 'total_item_qty', 'index' => 'total_item_qty', 'label' => '# of Items'],
            ['name' => 'total_subtotal', 'index' => 'total_subtotal', 'label' => 'Subtotal'],
            ['name' => 'total_tax', 'index' => 'total_tax', 'label' => 'Tax'],
            ['name' => 'total_shipping', 'index' => 'total_shipping', 'label' => 'Shipping'],
            ['name' => 'total_discount', 'index' => 'total_discount', 'label' => 'Discounts'],
            ['name' => 'total_amount', 'index' => 'total_amount', 'label' => 'Total'],
            ['name' => 'total_received', 'index' => 'total_received', 'label' => 'Received'],
            ['name' => 'total_refunded', 'index' => 'total_refunded', 'label' => 'Refunded'],
            ['name' => 'create_at', 'index' => 'o.create_at', 'label' => 'Created', 'hidden' => true],
        ];
        $config['filters'] = [
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
        $orm->select_expr('IFNULL(SUM(o.grand_total), 0)', 'total_amount');
        $tmpOrm = clone $orm;

        /** @var FCom_Core_View_BackboneGrid $view */
        $view = $this->view($this->_gridViewName);
        $config = $this->gridConfig();
        $filters = $this->_getFilters();
        $view->processGridFilters($config, $filters, $tmpOrm);
        $total = $tmpOrm->find_one()->get('total_amount');

        $offset = $this->BLocale->tzOffset() / 3600;
        if ($offset < 0) {
            $offset += 24 * 3600;
        }
        $orm->select_expr('MOD(HOUR(create_at) + ' . $offset . ', 24)', 'hour')
            ->select_expr('COUNT(o.id)', 'order_count')
            ->select_expr('IFNULL(SUM(o.item_qty), 0)', 'total_item_qty')
            ->select_expr('IFNULL(SUM(o.subtotal), 0)', 'total_subtotal')
            ->select_expr('IFNULL(SUM(o.tax_amount), 0)', 'total_tax')
            ->select_expr('IFNULL(SUM(o.shipping_price - o.shipping_discount), 0)', 'total_shipping')
            ->select_expr('IFNULL(SUM(o.discount_amount), 0)', 'total_discount')
            ->select_expr('IFNULL(SUM(o.amount_paid), 0)', 'total_received')
            ->select_expr('IFNULL(SUM(o.amount_refunded), 0)', 'total_refunded')
            ->select_expr("IFNULL(ROUND(100 * SUM(o.grand_total) / {$total}, 2), 0)", 'pc_total_amount')
            ->group_by_expr('HOUR(create_at)');
            //->order_by_expr('MOD(HOUR(create_at) + ' . $offset . ', 24) ASC');
    }
}