<?php

class FCom_Stock_Admin_Controller_Stock extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/stock';
    protected $_modelClass = 'FCom_Stock_Model_Sku';
    protected $_gridHref = 'stock';
    protected $_gridTitle = 'Stock Inventory';
    protected $_recordName = 'SKU';
    protected $_mainTableAlias = 's';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label'=>'ID', 'width'=>50, 'index' => 's.id'),
            array('name' => 'sku', 'label' => 'SKU', 'width' => 300, 'index' => 's.sku', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true, 'unique' => BApp::href('stock/unique'))),
            array('name' => 'qty_in_stock', 'label' => 'Qty In Stock', 'width' => 300, 'index' => 's.qty_in_stock', 'editable' => true, 'addable' => true,
                  'validation' => array('required' => true)),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                  'data'=> array('edit' => true, 'delete' => true)),
        );
        $config['actions'] = array(
//            'new' => array('caption' => 'Add New Customer Group', 'modal' => true),
            'edit' => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'sku', 'type' => 'text'),
            array('field' => 'qty_in_stock', 'type' => 'number-range'),
        );
        $config['new_button'] = '#add_new_sku';
        return $config;
    }

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each($post);
        $rows = BDb::many_as_array(FCom_Stock_Model_Sku::i()->orm()->where($data['key'], $data['value'])->find_many());
        BResponse::i()->json(array( 'unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])));
    }
}
