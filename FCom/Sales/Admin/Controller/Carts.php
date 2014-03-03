<?php
class FCom_Sales_Admin_Controller_Carts extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'carts';
    protected $_modelClass = 'FCom_Sales_Model_Cart';
    protected $_gridTitle = 'Carts';
    protected $_recordName = 'Cart';
    protected $_mainTableAlias = 'cart';

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
        $config['id'] = 'customer_grid_carts_'.$customer->id;
        $config['columns'] = array(
            array('type'=>'multiselect'),
            array('name' => 'id', 'label' => 'ID', 'width' =>70, 'hidden' => true),
            array('name' => 'session_id', 'label' => 'Session ID'),
            array('name' => 'item_num', 'label' => 'Total Items'),
            array('name' => 'item_qty', 'label' => 'Total Qty'),
            array('name' => 'subtotal', 'label' => 'Sub Total'),
            array('name' => 'discount_amount', 'label' => 'Discount'),
            array('name' => 'grand_total', 'label' => 'Grand Total'),
            array('name' => 'shipping_method', 'label' => 'Shipping Method'),
            array('name' => 'payment_method', 'label' => 'Payment Method'),
            array('type' => 'input', 'name' => 'status', 'label' => 'Status', 'editor' => 'select', 'editable' => true,
                  'options' => FCom_Sales_Model_Cart::i()->fieldOptions('status')),
            array('name' => 'create_at', 'label' => 'Created'),
        );
        $config['filters'] = array(
            array('field' => 'create_at', 'type' => 'date-range'),
            array('field' => 'grandtotal', 'type' => 'number-range'),
            array('field' => 'status', 'type' => 'multiselect'),
        );
        $config['orm'] = $config['orm']->where('customer_id', $customer->id);

        return array('config' => $config);
    }
}
