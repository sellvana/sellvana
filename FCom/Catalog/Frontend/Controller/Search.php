<?php

class FCom_Catalog_Frontend_Controller_Search extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $filter = BRequest::i()->get('f');

        $catName = BRequest::i()->params('category');
        if ($catName === '' || is_null($catName)) {
            $this->forward(false);
            return;
        }
        $category = FCom_Catalog_Model_Category::i()->load($catName, 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }

        $productsORM = FCom_Catalog_Model_Product::i()->searchProductOrm($q, $filter, $category);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('current_query', $q)
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
        $layout->view('breadcrumbs')->set('crumbs', $crumbs);

        if ($category->layout_update) {
            $layoutUpdate = BYAML::parse($category->layout_update);
            if (!is_null($layoutUpdate)) {
                BLayout::i()->addLayout('category_page', $layoutUpdate)->applyLayout('category_page');
            } else {
                BDebug::warning('Invalid layout update for CMS page');
            }
        }

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        $layout->view('catalog/product/pager')->set(array('query' => $q, 'filters' => $filter));
        $layout->view('catalog/nav')->set(array('category' => $category));

        FCom_Core_Main::i()->lastNav(true);

        $this->layout('/catalog/category');
    }

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $filter = BRequest::i()->get('f');

        $q = FCom_Catalog_Model_SearchAlias::i()->processSearchQuery($q);

        $productsORM = FCom_Catalog_Model_Product::i()->searchProductOrm($q, $filter);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', array('data'=>&$productsData));

        $category = FCom_Catalog_Model_Category::i()->orm()->where_null('parent_id')->find_one();
        BApp::i()
            ->set('current_query', $q)
            ->set('current_category', $category)
            ->set('products_data', $productsData);

        $rowsViewName = 'catalog/product/'.(BRequest::i()->get('view')=='list' ? 'list' : 'grid');
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];

        FCom_Catalog_Model_SearchHistory::i()->addSearchHit($q, $productsData['state']['c']);

        $layout->view('breadcrumbs')->set('crumbs', array('home', array('label'=>'Search: '.$q, 'active'=>true)));
        $layout->view('catalog/search')->set('query', $q);
        $layout->view('catalog/product/pager')->set('filters', $filter);
        $layout->view('catalog/product/pager')->set('query', $q);

        FCom_Core_Main::i()->lastNav(true);
        $this->layout('/catalog/search');
    }


}
