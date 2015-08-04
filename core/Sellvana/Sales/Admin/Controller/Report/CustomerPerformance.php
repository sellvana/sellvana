<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_Report_CustomerPerformance
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_Admin_Controller_Report_CustomerPerformance extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Customer_Model_Customer';
    protected $_mainTableAlias = 'c';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/customer_performance';
    protected $_gridHref = 'sales/report/customer_performance';
    protected $_gridTitle = 'Customer Performance';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['name' => 'id', 'index' => 'c.id', 'label' => 'Customer Id'],
            ['name' => 'customer_name', 'index' => 'customer_name', 'label' => 'Customer Name'],
            ['name' => 'email', 'index' => 'c.email', 'label' => 'Customer Email'],
            ['name' => 'title', 'index' => 'cg.title', 'label' => 'Customer Group'],
            ['name' => 'lifetime_sales', 'index' => 'lft.lifetime_sales', 'label' => 'Lifetime Sales'],
            ['name' => 'lifetime_order_count', 'index' => 'lft.lifetime_order_count', 'label' => 'Lifetime Number of Orders'],
            ['name' => 'lifetime_avg_amount', 'index' => 'lft.lifetime_avg_amount', 'label' => 'Lifetime Avg Order Value'],
            ['name' => 'lifetime_item_qty', 'index' => 'lft.lifetime_item_qty', 'label' => 'Lifetime # of units purchased'],
            ['name' => 'lifetime_refund_qty', 'index' => 'lft.lifetime_refund_qty', 'label' => 'Lifetime # of Refunds'],
            ['name' => 'lifetime_refund_amount', 'index' => 'lft.lifetime_refund_amount', 'label' => 'Lifetime $Refunds'],
            ['name' => 'create_at', 'index' => 'c.create_at', 'label' => 'Date Created'],
            ['name' => 'days_since_last_order', 'index' => 'days_since_last_order', 'label' => 'Days since last order'],
            ['name' => 'period_sales', 'index' => 'period_sales', 'label' => '$Total during specified Period'],
            ['name' => 'period_order_count', 'index' => 'period_order_count', 'label' => 'Total Orders During Specified Period'],
            ['name' => 'period_item_qty', 'index' => 'period_item_qty', 'label' => '# of Items During Specified Period'],
            ['name' => 'period_avg_amount', 'index' => 'period_avg_amount', 'label' => 'AOV During Period'],
            ['name' => 'period_refund_qty', 'index' => 'period_refund_qty', 'label' => '# Refunds During Period'],
            ['name' => 'period_refund_amount', 'index' => 'period_refund_amount', 'label' => '$Refunds During Period'],

            ['name' => 'order_create_at', 'label' => 'Created', 'index' => 'o.create_at', 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'order_create_at', 'type' => 'date-range'],
        ];

        return $config;
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tOrder = $this->Sellvana_Sales_Model_Order->table();

        $orm->left_outer_join('Sellvana_Sales_Model_Order', 'o.customer_id = c.id', 'o')
            ->select_expr("CONCAT(c.firstname, ' ', c.lastname)", 'customer_name')
            ->select_expr('IFNULL(SUM(o.grand_total), 0)', 'period_sales')
            ->select_expr('IFNULL(SUM(o.item_qty), 0)', 'period_item_qty')
            ->select_expr('IFNULL(AVG(o.grand_total), 0)', 'period_avg_amount')
            ->select_expr('IFNULL(SUM(o.amount_refunded), 0)', 'period_refund_amount')
            ->select_expr('COUNT(o.id)', 'period_order_count')
            ->select_expr('DATEDIFF(NOW(), MAX(o.create_at))', 'tmp_days_since_last_order')
            ->left_outer_join('Sellvana_CustomerGroups_Model_Group', 'cg.id = c.customer_group', 'cg')
            ->raw_join("LEFT JOIN (
                SELECT sub_c.id as `lft_customer_id`, IFNULL(SUM(sub_o.grand_total), 0) as `lifetime_sales`, IFNULL(SUM(sub_o.item_qty), 0) as `lifetime_item_qty`,
                    IFNULL(AVG(sub_o.grand_total), 0) as `lifetime_avg_amount`, IFNULL(SUM(sub_o.amount_refunded), 0) as `lifetime_refund_amount`,
                    COUNT(sub_o.id) as `lifetime_order_count`, DATEDIFF(NOW(), MAX(sub_o.create_at)) as `days_since_last_order`
                FROM {$tCustomer} `sub_c`
                LEFT JOIN {$tOrder} `sub_o` ON (sub_o.customer_id = sub_c.id)
                GROUP BY sub_c.id
            )", 'lft.lft_customer_id = c.id', 'lft')
            ->raw_join("LEFT JOIN (
                SELECT customer_id, COUNT(id) as `lifetime_refund_qty`
                FROM {$tOrder}
                WHERE amount_refunded > 0
                GROUP BY customer_id
            )", "lft_refund.customer_id = c.id", 'lft_refund')
            ->select('lft.*')
            ->select('cg.title')
            ->select_expr('IFNULL(lft_refund.lifetime_refund_qty, 0)', 'lifetime_refund_qty')
            ->select_expr('COUNT(IF(o.amount_refunded > 0, 1, NULL))', 'period_refund_qty')
            ->group_by('c.id');
    }

}