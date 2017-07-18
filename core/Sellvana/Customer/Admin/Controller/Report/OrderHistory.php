<?php

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
    protected $_gridTitle = (('Order History'));

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $customers = $this->Sellvana_Customer_Model_Customer->orm('c')->find_many();
        $customersOptions = [];
        foreach ($customers as $customer) {
            $customersOptions[$customer->get('id')] = $customer->get('firstname') . ' ' . $customer->get('lastname');
        }

        $config['columns'] = [
            ['name' => 'period_type', 'options' => $this->_periodTypes, 'hidden' => true],
            ['name' => 'period', 'index' => 'period', 'width' => 70],
            ['name' => 'customer_id', 'index' => 'customer_id', 'options' => $customersOptions],
            ['name' => 'order_count', 'index' => 'order_count'],
            ['name' => 'item_count', 'index' => 'item_count'],
            ['name' => 'total_amount', 'index' => 'total_amount', 'cell' => 'currency'],
            ['name' => 'total_received', 'index' => 'total_received', 'cell' => 'currency'],
            ['name' => 'total_refunded', 'index' => 'total_refunded', 'cell' => 'currency'],

            ['name' => 'create_at', 'index' => 'o.create_at', 'hidden' => true, 'cell' => 'datetime'],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'period_type', 'type' => 'multiselect', 'callback' => 'periodTypeCallback'],
            ['field' => 'customer_id', 'type' => 'multiselect', 'min_input_length' => 1],
        ];

        return $config;
    }

    /**
     * @return array
     */
    protected function _getFieldLabels()
    {
        return [
            'period' => (('Period')),
            'customer_id' => (('Customer')),
            'order_count' => '# of Orders',
            'item_count' => '# of Items',
            'total_amount' => (('Total Sales')),
            'total_received' => (('Total $Received')),
            'total_refunded' => (('Total $Refunded')),
            'create_at' => (('Created')),
            'period_type' => (('Group by')),
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->select_expr('COUNT(o.id)', 'order_count')
            ->select_expr('MIN(o.create_at)', 'create_at')
            ->select_expr('IFNULL(SUM(o.item_qty), 0)', 'item_count')
            ->select_expr('IFNULL(SUM(o.grand_total), 0)', 'total_amount')
            ->select_expr('IFNULL(SUM(o.amount_paid), 0)', 'total_received')
            ->select_expr('IFNULL(SUM(o.amount_refunded), 0)', 'total_refunded')
            ->where_not_null('o.customer_id')
            ->group_by('o.customer_id');
    }
}