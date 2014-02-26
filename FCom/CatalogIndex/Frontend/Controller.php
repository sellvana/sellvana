<?php

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
#echo "<pre>"; debug_print_backtrace(); print_r(BRouting::i()->currentRoute()); exit;
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }

        $layout = BLayout::i();
        $q = BRequest::i()->get('q');

        $productsData = FCom_CatalogIndex_Indexer::i()->searchProducts(null, null, null, array('category'=>$category));
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_orm', array('data'=>$productsData['orm']));
        $r = BRequest::i()->get();
        $r['sc'] = '';
        $paginated = $productsData['orm']->paginate($r);
        $paginated['state']['sc'] = BRequest::i()->get('sc');
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core_Main::i()->lastNav(true);

        $head = $this->view('head');
        $crumbs = array('home');
        foreach ($category->ascendants() as $c) {
            $nodeName = $c->get('node_name');
            if ($nodeName) {
                $crumbs[] = array('label'=>$nodeName, 'href'=>$c->url());
                $head->addTitle($nodeName);
            }
        }
        $crumbs[] = array('label'=>$category->get('node_name'), 'active'=>true);
        $head->addTitle($category->get('node_name'));
        $layout->view('breadcrumbs')->set('crumbs', $crumbs);

        $layout->view('catalog/search')->set('query', $q);

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->set('sort_options', FCom_CatalogIndex_Model_Field::i()->getSortingArray());
        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);
        $this->layout('/catalog/category');

        if ($category->layout_update) {
            $layoutUpdate = BYAML::parse($category->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('category_page', $layoutUpdate)->applyLayout('category_page');
            } else {
                BDebug::warning('Invalid layout update for CMS page');
            }
        }
    }

    public function action_search()
    {
        $req = BRequest::i();
        $q = $req->get('q');
        if (!$q) {
            BResponse::i()->redirect('');
            return;
        }
        $q = BRequest::i()->get('q');

        $q = FCom_Catalog_Model_SearchAlias::i()->processSearchQuery($q);

        $productsData = FCom_CatalogIndex_Indexer::i()->searchProducts($q);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_orm', array('data'=>$productsData['orm']));
        $paginated = $productsData['orm']->paginate();
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core_Main::i()->lastNav(true);
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->set('crumbs', array('home', array('label'=>'Search: '.$q, 'active'=>true)));
        $layout->view('catalog/search')->set('query', $q);

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        FCom_Catalog_Model_SearchHistory::i()->addSearchHit($q, $productsData['state']['c']);

        $layout->view('catalog/product/pager')->set('sort_options', FCom_CatalogIndex_Model_Field::i()->getSortingArray());
        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);

        $this->layout('/catalog/search');
    }
}
