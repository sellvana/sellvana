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
        $config['columns'] = array(
            array('name' => 'id', 'index'=>'o.id', 'label' => 'Status id', 'width' =>70),
            array('name' => 'name', 'index'=>'name', 'label' => 'Label', 'href' => BApp::href('orderstatus/form/?id=:id')),
            array('name' => 'code', 'index'=>'code', 'label' => 'Code'),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'data' => array('edit' => array('href' => BApp::href('orderstatus/form/?id='), 'col'=>'id'),'delete' => true)),
        );

        return $config;
    }
}
