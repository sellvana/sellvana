<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
    protected $_navPath = 'sales/reports';
    protected $_gridHref = 'sales/report/byCustomerGroup';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'group_id', 'index' => 'group_id', 'label' => 'Customer Group ID', 'width' => 70],
            ['name' => 'group_title', 'index' => 'group_title', 'label' => 'Customer Group', 'width' => 100],

            ['name' => 'subtotal_aggr', 'index' => 'subtotal_aggr', 'label' => 'Subtotal', 'width' => 70],
            ['name' => 'tax_amount_aggr', 'index' => 'tax_amount_aggr', 'label' => 'Tax'],
            ['name' => 'shipping_price_aggr', 'index' => 'shipping_price_aggr', 'label' => 'Shipping'],
            ['name' => 'discount_amount_aggr', 'label' => 'Discounts', 'index' => 'discount_amount_aggr'],
            ['name' => 'grand_total_aggr', 'label' => 'Total', 'index' => 'grand_total_aggr'],

            ['name' => 'amount_paid_aggr', 'label' => 'Received', 'index' => 'amount_paid_aggr'],
            ['name' => 'amount_refunded_aggr', 'label' => 'Refunded', 'index' => 'amount_refunded_aggr'],

            ['name' => 'item_qty_aggr', 'label' => '# of Units Sold', 'index' => 'item_qty_aggr'],
            ['name' => 'pc_of_sales', 'label' => '% of sales', 'index' => 'pc_of_sales'],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'o.create_at', 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
        ];
        $config['visualizations'] = [
            ['type' => 'pie', 'label_field' => 'group_title', 'value_field' => 'subtotal_aggr']
        ];

        return $config;
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $tOrder = $this->Sellvana_Sales_Model_Order->table();
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
            ->select_expr('SUM(o.grand_total)', 'grand_total_aggr')
            ->select_expr('SUM(o.item_qty)', 'item_qty_aggr')
            ->raw_join('INNER JOIN (SELECT SUM(grand_total) as `amount` FROM ' . $tOrder . ')', '1=1', 'total')
            ->select_expr('ROUND(100 * SUM(o.grand_total) / `total`.`amount`, 2)', 'pc_of_sales')
            ->group_by('cg.id');
    }
}