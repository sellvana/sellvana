<?php

class FCom_Catalog_Frontend_Controller_Search extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $filter = BRequest::i()->get('f');

        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(true);
            return $this;
        }

        $productsORM = FCom_Catalog_Model_Product::i()->searchProductOrm($q, $filter, $category);
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('products_data', $productsData);

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

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='grid' ? 'grid' : 'list');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->query = $q;
        $layout->view('catalog/product/pager')->filters = $filter;

        BLayout::i()->layout(array(
            '/catalog/category'=>array(
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'sidebar-left', 'views'=>array('catalog/category/sidebar'))
            ),
         ));

        FCom_Core::lastNav(true);

        $this->layout('/catalog/category');
    }

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $filter = BRequest::i()->get('f');

        $productsORM = FCom_Catalog_Model_Product::i()->searchProductOrm($q, $filter);
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_data', array('data'=>&$productsData));

        $category = FCom_Catalog_Model_Category::orm()->where_null('parent_id')->find_one();
        BApp::i()
            ->set('current_query', $q)
            ->set('current_category', $category)
            ->set('products_data', $productsData);

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='grid' ? 'grid' : 'list');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/product/pager')->filters = $filter;
        $layout->view('catalog/product/pager')->query = $q;

        BLayout::i()->layout(array(
            '/catalog/search'=>array(
                array('view', 'root', 'set'=>array('show_left_col'=>true)),
                array('hook', 'sidebar-left', 'views'=>array('catalog/category/sidebar'))
            ),
         ));

        FCom_Core::lastNav(true);
        $this->layout('/catalog/search');
    }


}