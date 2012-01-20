<?php

class FCom_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid');
        $grid->config = array(
            'grid' => array(
                'caption'       => 'Products',
                'id'            => 'products',
                'url'           => 'products/grid/data',
                'editurl'       => 'products/grid/data',
                'colModel'      => array(
                    array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>55),
                    array('name'=>'product_name', 'label'=>'Name', 'index'=>'product_name', 'width'=>250,
                        'formatter'=>'showlink', 'formatoptions'=>array(
                            'baseLinkUrl' => BApp::m('FCom_Catalog')->baseHref().'/products/view/',
                        )),
                    array('name'=>'manuf_sku', 'label'=>'Mfr Part #', 'index'=>'manuf_sku', 'width'=>100),
                    array('name'=>'manuf_vendor_name', 'label'=>'Mfr', 'index'=>'manuf_vendor_name', 'width'=>100),
                    array('name'=>'create_dt', 'label'=>'Created', 'index'=>'p.create_dt', 'formatter'=>'date'),
                ),
                'sortname'      => 'p.id',
                'sortorder'     => 'asc',
            ),
            'navGrid' => array(),
        );
        BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::index', array('grid'=>$grid));
        $this->layout('/catalog/products');
    }

    public function action_view()
    {
        $id = BRequest::i()->params('id');
        if (!$id) {
            $id = BRequest::i()->get('id');
        }
        if ($id) {
            $product = FCom_Catalog_Model_Product::i()->load($id);
        }
        if (empty($product)) {
            BSession::i()->addMessage('Invalid product ID', 'error', 'admin');
            BResponse::i()->redirect(BApp::m('FCom_Catalog')->baseHref().'/products');
        }
        BLayout::i()->view('catalog/products/view')->product = $product;
        $this->layout('/catalog/products/view');
    }

    public function action_edit()
    {
        $id = BRequest::i()->params('id');
        if (!$id) {
            $id = BRequest::i()->get('id');
        }
        if ($id) {
            $product = FCom_Catalog_Model_Product::i()->load($id);
        }
        if (empty($product)) {
            BSession::i()->addMessage('Invalid product ID', 'error', 'admin');
            BResponse::i()->redirect(BApp::m('FCom_Catalog')->baseHref().'/products');
        }
        BLayout::i()->view('catalog/products/edit')->product = $product;
        $this->layout('/catalog/products/edit');
    }

    public function action_view_tab()
    {
        $r = BRequest::i();
        echo $r->params('id').': '.$r->params('tab');
        exit;
    }

    public function action_grid_config()
    {
        BResponse::i()->json(array(
            'url' => BApp::m('FCom_Catalog')->baseHref().'/products/grid/data',
            'grid' => array(
                //'forceFitColumns'=>true, // https://github.com/mleibman/SlickGrid/issues/223
                'editable'=>true,
                'autoEdit'=>false,
                'asyncEditorLoading'=>true,
                'enableAddRow'=>true,
                'enableCellNavigation'=>true,
                'enableColumnReorder'=>true
            ),
            'columns'=>array(
                array('id'=>'id', 'name'=>'#', 'field'=>'id', 'width'=>60, 'sortable'=>true),
                array('id'=>'product_name', 'name'=>'Name', 'field'=>'product_name', 'width'=>300, 'editor'=>'LongTextCellEditor', 'sortable'=>true),
                array('id'=>'base_price', 'name'=>'Price', 'field'=>'base_price', 'width'=>80, 'editor'=>'TextCellEditor', 'sortable'=>true),
                array('id'=>'manuf_sku', 'name'=>'Part #', 'field'=>'manuf_sku', 'width'=>100, 'sortable'=>true),
                #array('id'=>'%', 'name'=>'%', 'field'=>'percent', 'formatter'=>'GraphicalPercentCompleteCellFormatter', 'editor'=>'PercentCompleteCellEditor'),
                #array('id'=>'bool', 'name'=>'bool', 'field'=>'bool', 'formatter'=>'BoolCellFormatter', 'editor'=>'YesNoCheckboxCellEditor'),
            ),
            'sub'=>array('resize'=>'#details-pane/center'),
            'pager'=>array('id'=>'#products-grid-pager'),
            'columnpicker'=>true,
            //'checkboxSelector'=>true,
            //'reorder'=>true,
            'dnd'=>true,
            'undo'=>true,
        ));
    }

    public function action_grid_data()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')->select('p.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, 'FCom_Catalog_Admin_Controller_Products::grid_data');
        BResponse::i()->json($data);
    }
}