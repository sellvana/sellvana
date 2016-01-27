<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Report_Hour
 */
class Sellvana_Sales_Admin_Controller_Report_Day extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/day';
    protected $_gridHref = 'sales/report/day';
    protected $_gridTitle = 'Day of the Week';


    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['name' => 'day', 'index' => 'day', 'width' => 100],
            ['name' => 'order_count', 'index' => 'order_count'],
            ['name' => 'pc_orders', 'index' => 'pc_orders'],
            ['name' => 'total_item_qty', 'index' => 'total_item_qty'],
            ['name' => 'pc_total_item_qty', 'index' => 'pc_total_item_qty'],
            ['name' => 'total_subtotal', 'index' => 'total_subtotal'],
            ['name' => 'total_tax', 'index' => 'total_tax'],
            ['name' => 'total_shipping', 'index' => 'total_shipping'],
            ['name' => 'total_discount', 'index' => 'total_discount'],
            ['name' => 'total_amount', 'index' => 'total_amount'],
            ['name' => 'pc_total_amount', 'index' => 'pc_total_amount'],
            ['name' => 'total_received', 'index' => 'total_received'],
            ['name' => 'total_refunded', 'index' => 'total_refunded'],
            ['name' => 'create_at', 'index' => 'o.create_at', 'hidden' => true],
        ];
        $config['filters'] = [
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
            'day' => 'Day',
            'order_count' => '# of Orders',
            'pc_orders' => '% of Orders',
            'total_item_qty' => '# of Items',
            'pc_total_item_qty' => '% of Items',
            'total_subtotal' => 'Subtotal',
            'total_tax' => 'Tax',
            'total_shipping' => 'Shipping',
            'total_discount' => 'Discounts',
            'total_amount' => 'Total',
            'pc_total_amount' => '% of Total',
            'total_received' => 'Received',
            'total_refunded' => 'Refunded',
            'create_at' => 'Created',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $orm->select_expr('IFNULL(SUM(o.grand_total), 0)', 'total_amount')
            ->select_expr('IFNULL(SUM(o.item_qty), 0)', 'total_item_qty')
            ->select_expr('COUNT(o.id)', 'order_count');
        $tmpOrm = clone $orm;

        /** @var FCom_Core_View_BackboneGrid $view */
        $view = $this->view($this->_gridViewName);
        $config = $this->gridConfig();
        $filters = $this->_getFilters();
        $view->processGridFilters($config, $filters, $tmpOrm);
        $totals = $tmpOrm->find_one();

        $offset = $this->BLocale->tzOffset() / 3600;
        $offset = "INTERVAL {$offset} HOUR";
        $expr = "DAYOFWEEK(o.create_at + {$offset})";
        $orm->select_expr("DAYNAME(o.create_at + {$offset})", 'day')
            ->select_expr('COUNT(o.id)', 'order_count')
            ->select_expr('IFNULL(SUM(o.subtotal), 0)', 'total_subtotal')
            ->select_expr('IFNULL(SUM(o.tax_amount), 0)', 'total_tax')
            ->select_expr('IFNULL(SUM(o.shipping_price - o.shipping_discount), 0)', 'total_shipping')
            ->select_expr('IFNULL(SUM(o.discount_amount), 0)', 'total_discount')
            ->select_expr('IFNULL(SUM(o.amount_paid), 0)', 'total_received')
            ->select_expr('IFNULL(SUM(o.amount_refunded), 0)', 'total_refunded')
            ->select_expr("IFNULL(ROUND(100 * SUM(o.item_qty) / {$totals->get('total_item_qty')}, 2), 0)", 'pc_total_item_qty')
            ->select_expr("IFNULL(ROUND(100 * SUM(o.grand_total) / {$totals->get('total_amount')}, 2), 0)", 'pc_total_amount')
            ->select_expr("IFNULL(ROUND(100 * COUNT(o.id) / {$totals->get('order_count')}, 2), 0)", 'pc_orders')
            ->group_by_expr($expr);
    }
}