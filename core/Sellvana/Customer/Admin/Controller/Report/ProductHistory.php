<?php

/**
 * Class Sellvana_Customer_Admin_Controller_Report_ProductHistory
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Customer_Admin_Controller_Report_ProductHistory extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order_Item';
    protected $_mainTableAlias = 'oi';
    protected $_permission = 'customer/reports';
    protected $_navPath = 'reports/customer/product_history';
    protected $_gridHref = 'customer/report/product_history';
    protected $_gridTitle = 'Product History';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $customers = $this->Sellvana_Customer_Model_Customer->orm('c')->find_many();
        $customersOptions = [];
        foreach ($customers as $customer) {
            $customersOptions[$customer->get('id')] = $customer->get('firstname') . ' ' . $customer->get('lastname');
        }

        $config['columns'] = [
            ['name' => 'order_date', 'index' => 'o.create_at', 'cell' => 'datetime'],
            ['name' => 'customer_id', 'index' => 'customer_id', 'options' => $customersOptions],
            ['name' => 'unique_id', 'index' => 'o.unique_id'],
            ['name' => 'product_sku', 'index' => 'oi.product_sku'],
            ['name' => 'product_name', 'index' => 'oi.product_name'],
            ['name' => 'qty_ordered', 'index' => 'oi.qty_ordered'],
            ['name' => 'price', 'index' => 'oi.price', 'cell' => 'currency'],
            ['name' => 'row_total', 'index' => 'oi.row_total', 'cell' => 'currency'],
            ['name' => 'amount_refunded', 'index' => 'o_amount_refunded', 'cell' => 'currency'],
        ];
        $config['filters'] = [
            ['field' => 'order_date', 'type' => 'date-range'],
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
            'order_date' => 'Order Date',
            'customer_id' => 'Customer',
            'unique_id' => 'Order #',
            'product_sku' => 'Sku',
            'product_name' => 'Product Name',
            'qty_ordered' => 'Qty ordered',
            'price' => 'Price',
            'row_total' => 'Row total',
            'amount_refunded' => 'Refunded Amt',
        ];
    }

    /**
     * @param $orm BORM
     */
    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->join('Sellvana_Sales_Model_Order', 'oi.order_id = o.id', 'o')
            ->select(['unique_id' => 'o.unique_id', 'order_date' => 'o.create_at', 'customer_id' => 'o.customer_id'])
            ->select_expr('IFNULL(o.amount_refunded, 0)', 'o_amount_refunded')
            ->where_not_null('o.customer_id')
            ->group_by('oi.id');
    }
}