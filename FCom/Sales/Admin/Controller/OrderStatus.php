<?php

class FCom_Sales_Admin_Controller_OrderStatus extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'orderstatus';
    protected $_modelClass = 'FCom_Sales_Model_Order_Status';
    protected $_gridTitle = 'Orders Status';
    protected $_recordName = 'Order status';
    protected $_mainTableAlias = 'os';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] = array_replace_recursive($config['grid']['columns'], array(
            'id' => array('index'=>'o.id', 'label' => 'Status id', 'width' =>70),
            'name' => array('index'=>'name', 'label' => 'Label'),
            'code' => array('index'=>'code', 'label' => 'Code')
        ));
        $config['custom']['dblClickHref'] = BApp::href('orderstatus/form/?id=');

        return $config;
    }
}