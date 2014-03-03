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
            array('type'=>'row_select'),
            array('name' => 'id', 'index' => 'o.id', 'label' => 'ID', 'width' => 70),
            array('name' => 'code', 'index' => 'code', 'label' => 'Code',
                  'addable' => true, 'editable' => true, 'validation' => array('required' => true, 'unique' => BApp::href('orderstatus/unique'))),
            array('name' => 'name', 'index' => 'name', 'label' => 'Label',
                  'addable' => true, 'editable' => true, 'validation' => array('required' => true, /*'unique' => BApp::href('orderstatus/unique')*/)),
            array('type'=>'btn_group', 'buttons' => array( array('name'=>'edit'), array('name'=>'delete' ) ) )
        );
        $config['actions'] = array(
            'new'    => array('caption' => 'Add New Order Status', 'modal' => true),
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'code', 'type' => 'text'),
        );
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(array( 'actions' => array( 'new' => '')));
    }

    /**
     * ajax check code is unique
     */
    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each($post);
        $rows = BDb::many_as_array(FCom_Sales_Model_Order_Status::i()->orm()->where($data['key'], $data['value'])->find_many());
        BResponse::i()->json(array( 'unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])));
    }
}
