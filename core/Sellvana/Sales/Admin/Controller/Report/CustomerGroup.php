<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Report_CustomerGroup
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_Admin_Controller_Report_CustomerGroup extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/customer_groups';
    protected $_gridHref = 'sales/report/customer_groups';
    protected $_gridTitle = 'Customer Groups';


    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'group_id', 'index' => 'group_id', 'width' => 70],
            ['name' => 'group_title', 'index' => 'group_title', 'width' => 100],

            ['name' => 'subtotal_aggr', 'index' => 'subtotal_aggr'],
            ['name' => 'tax_amount_aggr', 'index' => 'tax_amount_aggr'],
            ['name' => 'shipping_price_aggr', 'index' => 'shipping_price_aggr'],
            ['name' => 'discount_amount_aggr', 'index' => 'discount_amount_aggr'],
            ['name' => 'grand_total_aggr', 'index' => 'grand_total_aggr'],

            ['name' => 'amount_paid_aggr', 'index' => 'amount_paid_aggr'],
            ['name' => 'amount_refunded_aggr', 'index' => 'amount_refunded_aggr'],

            ['name' => 'item_qty_aggr', 'index' => 'item_qty_aggr'],
            ['name' => 'pc_of_sales', 'index' => 'pc_of_sales'],
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
            'group_id' => 'Customer Group ID',
            'group_title' => 'Customer Group',
            'subtotal_aggr' => 'Subtotal',
            'tax_amount_aggr' => 'Tax',
            'shipping_price_aggr' => 'Shipping',
            'discount_amount_aggr' => 'Discounts',
            'grand_total_aggr' => 'Total',
            'amount_paid_aggr' => 'Received',
            'amount_refunded_aggr' => 'Refunded',
            'item_qty_aggr' => '# of Units Sold',
            'pc_of_sales' => '% of sales',
            'create_at' => 'Created',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);
        $orm->join('Sellvana_Customer_Model_Customer', 'c.id = o.customer_id', 'c')
            ->left_outer_join('Sellvana_CustomerGroups_Model_Group', 'cg.id = c.customer_group', 'cg')
            ->select_expr('IFNULL(SUM(o.grand_total), 0)', 'grand_total_aggr');
        $tmpOrm = clone $orm;

        /** @var FCom_Core_View_BackboneGrid $view */
        $view = $this->view($this->_gridViewName);
        $config = $this->gridConfig();
        $filters = $this->_getFilters();
        $view->processGridFilters($config, $filters, $tmpOrm);
        $total = $tmpOrm->find_one()->get('grand_total_aggr');

        $orm->join('Sellvana_Customer_Model_Customer', 'c.id = o.customer_id', 'c')
            ->left_outer_join('Sellvana_CustomerGroups_Model_Group', 'cg.id = c.customer_group', 'cg')
            ->select_expr('IFNULL(cg.title, "NO GROUP")', 'group_title')
            ->select_expr('IFNULL(cg.id, "")', 'group_id')
            ->select_expr('SUM(o.subtotal)', 'subtotal_aggr')
            ->select_expr('SUM(o.tax_amount)', 'tax_amount_aggr')
            ->select_expr('SUM(o.shipping_price - o.shipping_discount)', 'shipping_price_aggr')
            ->select_expr('SUM(o.discount_amount)', 'discount_amount_aggr')
            ->select_expr('SUM(o.amount_paid)', 'amount_paid_aggr')
            ->select_expr('SUM(o.amount_due)', 'amount_due_aggr')
            ->select_expr('IFNULL(SUM(o.amount_refunded), 0)', 'amount_refunded_aggr')
            ->select_expr('SUM(o.item_qty)', 'item_qty_aggr')
            ->select_expr("ROUND(100 * SUM(o.grand_total) / {$total}, 2)", 'pc_of_sales')
            ->group_by('cg.id');
    }
}