<?php

class FCom_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/catalog/products');
    }

    public function action_product()
    {
        $this->layout('/catalog/products/view');
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
        $orm = FCom_Catalog_Model_Product::i()->orm();
        $data = $orm->paginate(null, array('as_array'=>true));
        BResponse::i()->json($data);
    }
}