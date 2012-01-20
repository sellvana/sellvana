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

    public function action_grid_data()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')->select('p.*');
        $data = FCom_Admin_View_Grid::i()->processORM($orm, 'FCom_Catalog_Admin_Controller_Products::grid_data');
        BResponse::i()->json($data);
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
        $this->layout('/catalog/products/view');
        $layout = BLayout::i();
        $view = $layout->view('catalog/products/view');
        $curTab = BRequest::i()->request('tab');
        foreach ($view->tabs as $k=>$tab) {
            if (!$curTab) {
                $curTab = $k;
            }
            $layout->view($tab['view'])->set('product', $product);
        }
        $view->set(array(
            'product' => $product,
            'tab' => $curTab,
        ));
    }

    public function action_view_tab()
    {
        $r = BRequest::i();
        $outTabs = $r->request('tabs');
        if ($outTabs && is_string($outTabs)) {
            $outTabs = explode(',', $outTabs);
        }
        $id = $r->params('id');
        if (!$id) {
            $id = $r->request('id');
        }
        $mode = $r->request('mode');
        if (!$mode) {
            $mode = 'view';
        }
        $product = FCom_Catalog_Model_Product::i()->load($id);

        $this->layout('catalog_product_view_tabs');

        $out = array();
        if ($outTabs) {
            $layout = BLayout::i();
            $tabs = $layout->view('catalog/products/view')->tabs;
            foreach ($outTabs as $k) {
                $view = $layout->view($tabs[$k]['view']);
                if (!$view) {
                    BDebug::error('MISSING VIEW: '.$tabs[$k]['view']);
                    continue;
                }
                $out['tabs'][$k] = (string)$view->set(array(
                    'mode' => $mode,
                    'product' => $product,
                ));
            }
        }
        BResponse::i()->json($out);
    }

    public function action_edit_post()
    {
        $r = BRequest::i();
        $id = $r->params('id');
        $data = $r->post();


        //BPubSub::i()->fire('FCom_Catalog_Admin_Controller_Products::edit_post', array('id'=>$id, 'data'=>$data));

        if ($r->xhr()) {
            $this->forward('view_tab');
        } else {
            BResponse::i()->redirect(BApp::m('FCom_Catalog')->baseHref().'/products/view/'.$id);
        }
    }

}