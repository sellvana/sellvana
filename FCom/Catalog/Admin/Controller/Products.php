<?php

class FCom_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function gridColumns()
    {
        $columns = array(
            'id'=>array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>55, 'hidden'=>true),
            'product_name'=>array('name'=>'product_name', 'label'=>'Name', 'index'=>'p.product_name', 'width'=>250,
                'formatter'=>'showlink', 'formatoptions'=>array('baseLinkUrl'=>BApp::url('FCom_Catalog', '/products/form/'))),
            'manuf_sku'=>array('name'=>'manuf_sku', 'label'=>'Mfr Part #', 'index'=>'p.manuf_sku', 'width'=>100),
            'manuf_vendor_name'=>array('name'=>'manuf_vendor_name', 'label'=>'Mfr', 'index'=>'v.vendor_name', 'width'=>150),
            'create_dt'=>array('name'=>'create_dt', 'label'=>'Created', 'index'=>'p.create_dt', 'formatter'=>'date', 'width'=>100),
            'uom'=>array('name'=>'uom', 'label'=>'UOM', 'index'=>'p.uom', 'width'=>60),
        );
        BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::gridColumns', array('columns'=>&$columns));
        return $columns;
    }

    public function gridConfig()
    {
        $config = array(
            'grid' => array(
                'id'            => 'products',
                'url'           => BApp::url('FCom_Catalog', '/products/grid_data'),
                'colModel'      => array_values($this->gridColumns()),
                'sortname'      => 'p.id',
                'sortorder'     => 'asc',
                'multiselect'   => true,
                'multiselectWidth' => 30,
                //'afterInsertRow' => 'function(id,data,el) { console.log(id,data,el); }',
            ),
            'navGrid' => array(),
            //'searchGrid' => array('multipleSearch'=>true),
            'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
            array('navButtonAdd', 'caption' => 'Columns', 'title' => 'Reorder Columns', 'onClickButton' => 'function() {
                jQuery("#products").jqGrid("columnChooser");
            }'),
        );
        BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::gridConfig', array('config'=>&$config));
        return $config;
    }

    public function action_index()
    {
        $grid = BLayout::i()->view('jqgrid')->set('config', $this->gridConfig());
        BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::action_index', array('grid'=>$grid));
        $this->layout('/catalog/products');
    }

    public function action_grid_data()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')->select('p.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, 'FCom_Catalog_Admin_Controller_Products::action_grid_data');
        BResponse::i()->json($data);
    }

    public function action_form()
    {
        $id = BRequest::i()->params('id');
        if (!$id) {
            $id = BRequest::i()->get('id');
        }
        if ($id) {
            $product = FCom_Catalog_Model_Product::i()->load($id);
            if (empty($product)) {
                BSession::i()->addMessage('Invalid product ID', 'error', 'admin');
                BResponse::i()->redirect(BApp::m('FCom_Catalog')->baseHref().'/products');
            }
        } else {
            $product = FCom_Catalog_Model_Product::i()->create();
        }
        $this->layout('/catalog/products/form');
        $view = BLayout::i()->view('catalog/products/form');
        $this->initFormTabs($view, $product, $product->id ? 'view' : 'create');
    }

    public function action_form_tab()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        if (!$id) {
            $id = $r->request('id');
        }
        $product = FCom_Catalog_Model_Product::i()->load($id);
        $this->layout('catalog_product_form_tabs');
        $view = BLayout::i()->view('catalog/products/form');
        $this->outFormTabsJson($view, $product);
    }

    public function action_form_post()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        $data = $r->post();

        try {
            if ($id) {
                $model = FCom_Catalog_Model_Product::i()->load($id);
            } else {
                $model = FCom_Catalog_Model_Product::i()->create();
            }
            if (!empty($data['model'])) {
                $model->set($data['model']);
            }
            BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::form_post', array('id'=>$id, 'data'=>$data, 'model'=>$model));
            if (!empty($data['model'])) {
                $model->save();
                if (!$id) {
                    $id = $model->id;
                }
            }
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }

        if ($r->xhr()) {
            $this->forward('form_tab', null, array('id'=>$id));
        } else {
            BResponse::i()->redirect(BApp::m('FCom_Catalog')->baseHref().'/products/form/'.$id);
        }
    }

}