<?php

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_test()
    {
        FCom_CatalogIndex::i()->indexProducts(true);//FCom_Catalog_Model_Product::i()->orm()->find_many());
        FCom_CatalogIndex::i()->indexGC();
        $result = FCom_CatalogIndex::i()->findProducts('lorem', array(
            'category' => 'category-1/subcategory-1-1',
            'color'=>'Green',
            'size'=>'Medium',
        ), 'product_name');
        echo "<pre>";
        print_r($result['facets']);
        $pageData = $result['orm']->paginate();
        print_r($pageData);
        echo "</pre>";
    }

    public function action_category()
    {
#echo "<pre>"; debug_print_backtrace(); print_r(BFrontController::i()->currentRoute()); exit;
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(true);
            return $this;
        }

        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $sc = BRequest::i()->get('sc');
        $v = BRequest::i()->get('v');
        $s = BRequest::i()->get('s');
        $page = BRequest::i()->get('p');
        $resultPerPage = BRequest::i()->get('ps');

//        if (empty($f['category'])){
//            $categoryKey = FCom_IndexTank_Index_Product::i()->getCategoryKey($category);
//            $f['category'] = $categoryKey. ":".$category->node_name;
//        }

        $productsData = FCom_CatalogIndex::i()->findProducts($q, null, $s, array('category'=>$category));
        $paginated = $productsData['orm']->paginate();
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::i()->lastNav(true);

        $head = $this->view('head');
        $crumbs = array('home');
        foreach ($category->ascendants() as $c) {
            if ($c->node_name) {
                $crumbs[] = array('label'=>$c->node_name, 'href'=>$c->url());
                $head->addTitle($c->node_name);
            }
        }
        $crumbs[] = array('label'=>$category->node_name, 'active'=>true);
        $head->addTitle($category->node_name);
        $layout->view('breadcrumbs')->crumbs = $crumbs;

        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/search')->public_api_url = FCom_IndexTank_Search::i()->publicApiUrl();
        $layout->view('catalog/search')->index_name = FCom_IndexTank_Search::i()->indexName();

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='grid' ? 'grid' : 'list');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->sort_options = FCom_CatalogIndex_Model_Field::i()->getSortingArray();
        $layout->view('catalogindex/product/filters')->products_data = $productsData;

        $this->layout('/catalog/category');
    }

    public function action_search()
    {
        $req = BRequest::i();
        $q = $req->get('q');
        if (!$q) {
            BResponse::i()->redirect(BApp::href());
        }
        $sc = $req->get('sc');
        $f = $req->get('f');
        $v = $req->get('v');
        $page = $req->get('p');
        $resultPerPage = $req->get('ps');

        if(false == BConfig::i()->get('modules/FCom_IndexTank/index_name')){
            die('Please set up correct API URL at Admin Setting page');
        }

        $productsData = FCom_IndexTank_Search::i()->search($q, $sc, $f, $v, $page, $resultPerPage);
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/search')->public_api_url = FCom_IndexTank_Search::i()->publicApiUrl();
        $layout->view('catalog/search')->index_name = FCom_IndexTank_Search::i()->indexName();

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='grid' ? 'grid' : 'list');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->sort_options = FCom_CatalogIndex_Model_Field::i()->getSortingArray();
        $layout->view('indextank/product/filters')->state = $productsData['state'];

        $this->layout('/catalog/search');
    }
}