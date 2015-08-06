<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
            ['name' => 'order_date', 'index' => 'o.create_at', 'label' => 'Order Date'],
            ['name' => 'customer_id', 'index' => 'customer_id', 'label' => 'Customer', 'options' => $customersOptions],
            ['name' => 'unique_id', 'index' => 'o.unique_id', 'label' => 'Order #'],
            ['name' => 'product_sku', 'index' => 'oi.product_sku', 'label' => 'Sku'],
            ['name' => 'product_name', 'index' => 'oi.product_name', 'label' => 'Product Name'],
            ['name' => 'qty_ordered', 'index' => 'oi.qty_ordered', 'label' => 'Qty ordered'],
            ['name' => 'price', 'index' => 'oi.price', 'label' => 'Price'],
            ['name' => 'row_total', 'index' => 'oi.row_total', 'label' => 'Row total'],
            ['name' => 'qty_refunded', 'index' => 'oi.qty_refunded', 'label' => 'Refunded Qty'],
            ['name' => 'amount_refunded', 'index' => 'o.amount_refunded', 'label' => 'Refunded Amt'],
        ];
        $config['filters'] = [
            ['field' => 'order_date', 'type' => 'date-range'],
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

        $orm->join('Sellvana_Sales_Model_Order', 'oi.order_id = o.id', 'o')
            ->select(['unique_id' => 'o.unique_id', 'order_date' => 'o.create_at', 'customer_id' => 'o.customer_id'])
            ->select_expr('IFNULL(o.amount_refunded, 0)', 'amount_refunded')
            ->where_not_null('o.customer_id')
            ->group_by('oi.id');
    }
}