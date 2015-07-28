<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_Report_CustomerGroup
 *
 * @property Sellvana_CustomerGroups_Model_Group $Sellvana_CustomerGroups_Model_Group
 */
class Sellvana_Sales_Admin_Controller_Report_CustomerGroup extends  FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'sales/reports';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'id', 'index' => 'cg.id', 'label' => 'Customer Group ID', 'width' => 70],
            ['name' => 'group_title', 'index' => 'group_title', 'label' => 'Customer Group', 'width' => 100],

            ['name' => 'subtotal_aggr', 'index' => 'subtotal_aggr', 'label' => 'Subtotal', 'width' => 70],
            ['name' => 'tax_amount_aggr', 'index' => 'tax_amount_aggr', 'label' => 'Tax'],
            ['name' => 'shipping_price_aggr', 'index' => 'shipping_price_aggr', 'label' => 'Shipping'],
            ['name' => 'discount_amount_aggr', 'label' => 'Discounts', 'index' => 'discount_amount_aggr'],
            ['name' => 'grand_total', 'label' => 'Total', 'index' => 'o.id'],

            ['name' => 'amount_paid_aggr', 'label' => 'Received', 'index' => 'amount_paid_aggr'],
            ['name' => 'amount_refunded_aggr', 'label' => 'Refunded', 'index' => 'amount_refunded_aggr'],

            ['name' => 'item_qty_aggr', 'label' => '# of Units Sold', 'index' => 'item_qty_aggr'],
            ['name' => 'sales_percent', 'label' => '% of sales', 'index' => 'o.id'],
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

        $orm->join('Sellvana_Customer_Model_Customer', 'c.id = o.customer_id', 'c')
            ->left_outer_join('Sellvana_CustomerGroups_Model_Group', 'cg.id = c.customer_group', 'cg')
            //->select($this->_mainTableAlias . '.*')
            ->select_expr('IFNULL(cg.title, "NO GROUP")', 'group_title')
            ->select_expr('SUM(o.subtotal)', 'subtotal_aggr')
            ->select_expr('SUM(o.tax_amount)', 'tax_amount_aggr')
            ->select_expr('SUM(o.shipping_price - o.shipping_discount)', 'shipping_price_aggr')
            ->select_expr('SUM(o.discount_amount)', 'discount_amount_aggr')
            ->select_expr('SUM(o.amount_paid)', 'amount_paid_aggr')
            ->select_expr('SUM(o.amount_due)', 'amount_due_aggr')
            ->select_expr('SUM(o.amount_refunded)', 'amount_refunded_aggr')
            ->select_expr('SUM(o.item_qty)', 'item_qty_aggr')
            ->group_by('cg.id')
        ;


    }
}