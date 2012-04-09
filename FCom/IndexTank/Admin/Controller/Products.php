<?php

class FCom_IndexTank_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    protected $_permission = 'indextank/products';

    public function gridColumns()
    {
        $columns = array(
            'id'=>array('label'=>'ID', 'index'=>'p.id', 'width'=>55, 'hidden'=>true, 'frozen'=>true),
            'product_name'=>array('label'=>'Name', 'index'=>'p.product_name', 'width'=>250, 'frozen'=>true,
                'formatter'=>'showlink', 'formatoptions'=>array('baseLinkUrl'=>BApp::href('catalog/products/form/'))),
            'manuf_sku'=>array('label'=>'Mfr Part #', 'index'=>'p.manuf_sku', 'width'=>100),
            'create_dt'=>array('label'=>'Created', 'index'=>'p.create_dt', 'formatter'=>'date', 'width'=>100),
            'uom'=>array('label'=>'UOM', 'index'=>'p.uom', 'width'=>60),
        );
        BPubSub::i()->fire('FCom_IndexTank_Admin_Controller_Products::gridColumns', array('columns'=>&$columns));
        return $columns;
    }

    public function gridConfig()
    {
        $baseUrl = BApp::href('catalog/products/form/');
        $config = array(
            'grid' => array(
                'id'            => 'products',
                'url'           => BApp::href('catalog/products/grid_data'),
                'columns'       => $this->gridColumns(),
                'sortname'      => 'p.id',
                'sortorder'     => 'asc',
                'multiselect'   => true,
                'multiselectWidth' => 30,
                //'afterInsertRow' => 'function(id,data,el) { console.log(id,data,el); }',
                'ondblClickRow' => "function(rowid) {
                    location.href = '{$baseUrl}'+rowid;
                }",
            ),
            'custom'=>array('personalize'=>true),
            'navGrid' => array(),
            //'searchGrid' => array('multipleSearch'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            //'setFrozenColumns'=>array(),
        );
        BPubSub::i()->fire('FCom_IndexTank_Admin_Controller_Products::gridConfig', array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', $this->gridConfig());
        BPubSub::i()->fire(__METHOD__, array('grid'=>$grid));
        $this->layout('/products');
    }
}