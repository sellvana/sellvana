<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Admin_Controller_Carts
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 */

class FCom_Sales_Admin_Controller_Carts extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'carts';
    protected $_modelClass = 'FCom_Sales_Model_Cart';
    protected $_gridTitle = 'Carts';
    protected $_recordName = 'Cart';
    protected $_mainTableAlias = 'cart';
    protected $_permission = 'sales/carts';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        return $config;
    }

    /**
     * get grid config all carts of customer
     * @param $customer
     * @return array
     */
    public function customerCartsGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_grid_carts_' . $customer->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 70, 'hidden' => true],
            ['name' => 'session_id', 'label' => 'Session ID'],
            ['name' => 'item_num', 'label' => 'Total Items'],
            ['name' => 'item_qty', 'label' => 'Total Qty'],
            ['name' => 'subtotal', 'label' => 'Sub Total'],
            ['name' => 'discount_amount', 'label' => 'Discount'],
            ['name' => 'grand_total', 'label' => 'Grand Total'],
            ['name' => 'shipping_method', 'label' => 'Shipping Method'],
            ['name' => 'payment_method', 'label' => 'Payment Method'],
            ['type' => 'input', 'name' => 'state_overall', 'label' => 'Status', 'editor' => 'select', 'editable' => true,
                  'options' => $this->FCom_Sales_Model_Cart->fieldOptions('state_overall')],
            ['name' => 'create_at', 'label' => 'Created'],
        ];
        $config['filters'] = [
            ['field' => 'create_at', 'type' => 'date-range'],
            ['field' => 'grand_total', 'type' => 'number-range'],
            ['field' => 'status', 'type' => 'multiselect'],
        ];
        $config['orm'] = $config['orm']->where('customer_id', $customer->id);

        return ['config' => $config];
    }
}
