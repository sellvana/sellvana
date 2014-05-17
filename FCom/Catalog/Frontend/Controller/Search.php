<?php

class FCom_Catalog_Frontend_Controller_Search extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
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

        $this->layout('/catalog/category');
        $layout = BLayout::i();
        $pagerView = $layout->view('catalog/product/pager');

        $productsORM = FCom_Catalog_Model_Product::i()->searchProductOrm($q, $filter, $category);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_orm', ['orm' => $productsORM]);
        $productsData = $productsORM->paginate(null, [
            'ps' => $pagerView->default_page_size,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
            'page_size_options' => $pagerView->page_size_options,
        ]);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', ['data' => &$productsData]);

        BApp::i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        $head = $this->view('head');
        $crumbs = ['home'];
        $activeCatIds = [$category->id()];
        foreach ($category->ascendants() as $c) {
            if ($c->node_name) {
                $activeCatIds[] = $c->id();
                $crumbs[] = ['label' => $c->node_name, 'href' => $c->url()];
                $head->addTitle($c->node_name);
            }
        }
        $crumbs[] = ['label' => $category->node_name, 'active' => true];
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

        $rowsViewName = 'catalog/product/' . $pagerView->getViewAs();
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];
        $pagerView->state = $productsData['state'];
        $pagerView->setCanonicalPrevNext();

        $layout->view('catalog/product/pager')->set(['query' => $q, 'filters' => $filter]);
        $layout->view('catalog/nav')->set([
            'category' => $category,
            'active_ids' => $activeCatIds,
            'home_url' => BConfig::i()->get('modules/FCom_Catalog/url_prefix'),
        ]);


        FCom_Core_Main::i()->lastNav(true);

    }

    public function action_search()
    {
        $q = BRequest::i()->get('q');
        $filter = BRequest::i()->get('f');

        $this->layout('/catalog/category');
        $layout = BLayout::i();
        $pagerView = $layout->view('catalog/product/pager');

        $q = FCom_Catalog_Model_SearchAlias::i()->processSearchQuery($q);

        $productsORM = FCom_Catalog_Model_Product::i()->searchProductOrm($q, $filter);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_orm', ['data' => $productsORM]);
        $productsData = $productsORM->paginate(null, [
            'ps' => $pagerView->default_page_size,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
            'page_size_options' => $pagerView->page_size_options,
        ]);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', ['data' => &$productsData]);

        $category = FCom_Catalog_Model_Category::i()->orm()->where_null('parent_id')->find_one();
        BApp::i()
            ->set('current_query', $q)
            ->set('current_category', $category)
            ->set('products_data', $productsData);

        $rowsViewName = 'catalog/product/' . $pagerView->getViewAs();
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];
        $pagerView->state = $productsData['state'];
        $pagerView->setCanonicalPrevNext();

        FCom_Catalog_Model_SearchHistory::i()->addSearchHit($q, $productsData['state']['c']);

        $layout->view('breadcrumbs')->set('crumbs', ['home', ['label' => 'Search: ' . $q, 'active' => true]]);
        $layout->view('catalog/search')->set('query', $q);
        $pagerView->set('filters', $filter);
        $pagerView->set('query', $q);

        FCom_Core_Main::i()->lastNav(true);
    }


}
