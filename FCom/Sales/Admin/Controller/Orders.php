<?php

class FCom_Sales_Admin_Controller_Orders extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'orders';
    protected $_modelClass = 'FCom_Sales_Model_Order';
    protected $_gridTitle = 'Orders';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] = array_replace_recursive($config['grid']['columns'], array(
            'id' => array('index'=>'o.id', 'label' => 'Order id', 'width' =>70),
            'purchased_dt' => array('index'=>'o.purchased_dt', 'label' => 'Purchased on'),
            'billing_name' => array('label'=>'Bill to Name', 'index'=>'ab.billing_name'),
            'shipping_name' => array('label'=>'Ship to Name', 'index'=>'as.shipping_name'),
            'gt_base' => array('label'=>'GT (base)', 'index'=>'o.gt_base'),
            'balance' => array('label'=>'GT (paid)', 'index'=>'o.balance'),
            'discount' => array('label'=>'Discount', 'index'=>'o.discount_code'),
            'status' => array('label'=>'Status', 'index'=>'o.status'),
        ));
        $config['custom']['dblClickHref'] = BApp::href('orders/form/?id=');

        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Sales_Model_Address', 'o.id = ab.order_id and ab.atype="billing"', 'ab') //array('o.id','=','a.order_id')
            ->select_expr('CONCAT_WS(" ", ab.firstname,ab.lastname)','billing_name')
        ;
        $orm->left_outer_join('FCom_Sales_Model_Address', 'o.id = as.order_id and as.atype="shipping"', 'as') //array('o.id','=','a.order_id')
            ->select_expr('CONCAT_WS(" ", as.firstname,as.lastname)','shipping_name')
        ;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $args['view']->set(array(
            'actions' => array(
                'new' => '',
            ),
        ));
    }
}