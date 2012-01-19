<?php

class FCom_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid');
        $grid->config = array(
            'grid' => array(
                'url'           => 'products/grid/data',
                'editurl'       => 'products/grid/data',
                'colModel'      => array(
                    array('name'=>'id', 'label'=>'ID', 'index'=>'id', 'width'=>55, 'editable'=>true),
                    array('name'=>'product_name', 'label'=>'Name', 'index'=>'product_name', 'width'=>200, 'editable'=>true),
                    array('name'=>'manuf_sku', 'label'=>'Mfr Part #', 'index'=>'manuf_sku', 'width'=>100, 'editable'=>true),
                ),
                'onSelectRow'   => "function(id){
                    if(id && id!==lastsel3){
                        jQuery('#grid').jqGrid('restoreRow',lastsel3);
                        jQuery('#grid').jqGrid('editRow',id,true,pickdates);
                        lastsel3=id;
                    }
                }",
                'rowNum'        => 10,
                'rowList'       => array(10,20,30),
                'pager'         => true,
                'sortname'      => 'id',
                'height'        => '100%',
                'width' => 800,
                'viewrecords' => true,
                'sortorder' => "desc",
                'caption' => "Products",
            ),
        );
        BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::index', array('grid'=>$grid));
        $this->layout('/catalog/products');
    }

    public function action_view()
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
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p');
        BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::grid_data.orm', array('orm'=>$orm));
        $data = $orm->jqGridData();
        BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::grid_data.data', array('data'=>$data));
        BResponse::i()->json($data);
    }
}