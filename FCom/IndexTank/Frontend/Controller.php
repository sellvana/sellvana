<?php

class FCom_IndexTank_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(true);
            return $this;
        }
        $categoryKey = FCom_IndexTank_Index_Product::i()->getCategoryKey($category);
        $categoryKey .= ":".$category->node_name;

        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $sc = BRequest::i()->get('sc');
        $f = BRequest::i()->get('f');
        $v = BRequest::i()->get('v');
        $page = BRequest::i()->get('p');

        $f['category'] = $categoryKey;

        $productsData = FCom_IndexTank_Search::i()->search($q, $sc, $f, $v, $page, $resultPerPage);

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
    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $sc = BRequest::i()->get('sc');
        $f = BRequest::i()->get('f');
        $v = BRequest::i()->get('v');
        $page = BRequest::i()->get('p');
        $resultPerPage = BRequest::i()->get('ps');

        if(false == BConfig::i()->get('modules/FCom_IndexTank/index_name')){
            die('Please set up correct API URL at Admin Setting page');
        }

        $productsData = FCom_IndexTank_Search::i()->search($q, $sc, $f, $v, $page, $resultPerPage);

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
