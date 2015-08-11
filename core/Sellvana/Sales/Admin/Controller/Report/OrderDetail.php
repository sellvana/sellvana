<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Admin_Controller_Report_OrderDetail
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_Sales_Admin_Controller_Report_OrderDetail extends FCom_Admin_Controller_Abstract_Report
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'Sellvana_Sales_Model_Order';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';
    protected $_permission = 'sales/reports';
    protected $_navPath = 'reports/sales/order_detail';
    protected $_gridHref = 'sales/report/order_detail';
    protected $_gridTitle = 'Order Details';

    public function gridConfig()
    {
        $this->_selectModels['o'] = $this->Sellvana_Sales_Model_Order;
        $this->_selectModels['c'] = $this->Sellvana_Customer_Model_Customer;

        $config = parent::gridConfig();
        $config['columns'] = [
            ['name' => 'create_at', 'index' => 'o.create_at', 'hidden' => true],
        ];

        /** @var FCom_Core_Model_Abstract $model */
        foreach ($this->_selectModels as $alias => $model) {
            $table = $model->table();
            $fields = BDb::ddlFieldInfo($table);
            foreach ($fields as $field) {
                $config['columns'][] = [
                    'name' => $alias . '_' . $field->orm->get('Field'),
                    'index' => $alias . '.' . $field->orm->get('Field'),
                    'hidden' => true
                ];
            }
        }

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
            ->join('Sellvana_Sales_Model_Order_Item', 'oi.order_id = o.id', 'oi')
            ->select_expr('GROUP_CONCAT(oi.product_sku SEPARATOR ", ")', 'inventory_sku')
            ->group_by('o.id');

        $this->_selectAllFields($orm);
    }
}