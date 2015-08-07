<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Admin_Controller_Report_OrderHistory
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Customer_Admin_Controller_Report_OrderHistory extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'customer/reports';
    protected $_navPath = 'reports/customer/order_history';
    protected $_gridHref = 'customer/report/order_history';
    protected $_gridTitle = 'Order History';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $customers = $this->Sellvana_Customer_Model_Customer->orm('c')->find_many();
        $customersOptions = [];
        foreach ($customers as $customer) {
            $customersOptions[$customer->get('id')] = $customer->get('firstname') . ' ' . $customer->get('lastname');
        }

        $config['columns'] = [
            ['name' => 'period', 'index' => 'period', 'label' => 'Period', 'width' => 70],
            ['name' => 'customer_id', 'index' => 'customer_id', 'label' => 'Customer', 'options' => $customersOptions],
            ['name' => 'order_count', 'index' => 'order_count', 'label' => '# of Orders'],
            ['name' => 'item_count', 'index' => 'item_count', 'label' => '# of Items'],
            ['name' => 'total_amount', 'index' => 'total_amount', 'label' => 'Total Sales'],
            ['name' => 'total_received', 'index' => 'total_received', 'label' => 'Total $Received'],
            ['name' => 'total_refunded', 'index' => 'total_refunded', 'label' => 'Total $Refunded'],

            ['name' => 'create_at', 'label' => 'Created', 'index' => 'o.create_at', 'hidden' => true],
            ['name' => 'period_type', 'label' => 'Period', 'options' => $this->_periodTypes, 'hidden' => true],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'period_type', 'type' => 'multiselect', 'callback' => 'periodTypeCallback'],
            ['field' => 'customer_id', 'type' => 'multiselect'],
        ];

        return $config;
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->select_expr('COUNT(o.id)', 'order_count')
            ->select_expr('IFNULL(SUM(o.item_qty), 0)', 'item_count')
            ->select_expr('IFNULL(SUM(o.grand_total), 0)', 'total_amount')
            ->select_expr('IFNULL(SUM(o.amount_paid), 0)', 'total_received')
            ->select_expr('IFNULL(SUM(o.amount_refunded), 0)', 'total_refunded')
            ->where_not_null('o.customer_id')
            ->group_by('o.customer_id');
    }
}