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
            'id' => array('index'=>'o.id'),
            'firstname' => array('label'=>'First Name', 'index'=>'c.firstname'),
            'lastname' => array('label'=>'Last Name', 'index'=>'c.lastname'),
            'email' => array('label'=>'Email', 'index'=>'c.email'),
            'item_qty' => array('label'=>'Item quantity', 'index'=>'o.item_qty'),
            'subtotal' => array('label'=>'Subtotal', 'index'=>'o.subtotal'),
            'balance' => array('label'=>'Grand total', 'index'=>'o.balance'),
            'shipping_method' => array('label'=>'Shipping Method', 'index'=>'o.shipping_method'),
            'payment_method' => array('label'=>'Payment Method', 'index'=>'o.payment_method'),
            'status' => array('label'=>'Status', 'index'=>'o.status'),
        ));
        $config['custom']['dblClickHref'] = BApp::href('orders/form/?id=');

        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Customer_Model_Customer', array('c.id','=','o.user_id'), 'c')
            ->select(array('c.firstname', 'c.lastname', 'c.email'))
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