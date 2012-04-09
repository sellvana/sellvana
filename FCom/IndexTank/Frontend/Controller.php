<?php

class FCom_IndexTank_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $q = trim($q);
        if (!$q) {
            BResponse::i()->redirect(BApp::baseUrl());
        }

        $productsORM = FCom_IndexTank_Index_Product::i()->search($q);


        //$and = array();
        //foreach ($qs as $k) $and[] = array('product_name like ?', '%'.$k.'%');
        //$productsORM = FCom_Catalog_Model_Product::i()->factory()->where_complex(array('OR'=>array('manuf_sku'=>$q, 'AND'=>$and)));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_orm', array('data'=>$productsORM));
        
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('indextank/search')->query = $q;
        $layout->view('indextank/product/list')->products_data = $productsData;

        $this->layout('/indextank/search');
        BResponse::i()->render();
    }

}
