<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
#echo "<pre>"; debug_print_backtrace(); print_r(BRouting::i()->currentRoute()); exit;
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

        $q = BRequest::i()->get('q');

        $productsData = FCom_CatalogIndex_Indexer::i()->searchProducts(null, null, false, [
            'category' => $category,
        ]);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_orm', ['orm' => $productsData['orm']]);
        $r = BRequest::i()->get();
        $paginated = $productsData['orm']->paginate($r, [
            'ps' => $pagerView->default_page_size,
            'page_size_options' => $pagerView->page_size_options,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
        ]);
        //$paginated['state']['sc'] = BRequest::i()->get('sc');
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        //$productsData['state']['sc'] = BRequest::i()->get('sc');
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', ['data' => &$productsData]);

        BApp::i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core_Main::i()->lastNav(true);

        $head = $layout->view('head');
        $crumbs = ['home'];
        $activeCatIds = [$category->id()];
        foreach ($category->ascendants() as $c) {
            $nodeName = $c->get('node_name');
            if ($nodeName) {
                $activeCatIds[] = $c->id();
                $crumbs[] = ['label' => $nodeName, 'href' => $c->url()];
                $head->addTitle($nodeName);

            }
        }
        $crumbs[] = ['label' => $category->get('node_name'), 'active' => true];
        $category->set('is_active', 1);

        $head->addTitle($category->get('node_name'));
        $layout->view('breadcrumbs')->set('crumbs', $crumbs);

        $layout->view('catalog/search')->set('query', $q);

        $rowsViewName = 'catalog/product/' . $pagerView->getViewAs();
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->category = $category;
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];
        $pagerView->state = $productsData['state'];
        $pagerView->setCanonicalPrevNext();

        $layout->view('catalog/nav')->set([
            'category' => $category,
            'active_ids' => $activeCatIds,
            'home_url' => BConfig::i()->get('modules/FCom_Catalog/url_prefix'),
        ]);

        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);

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
        if (is_array($q)) {
            $q = join(' ', $q);
        }
        if ($q !== '' && !is_null($q)) {
            $q = FCom_Catalog_Model_SearchAlias::i()->processSearchQuery($q);
        }

        $this->layout('/catalog/search');
        $layout = BLayout::i();
        $pagerView = $layout->view('catalog/product/pager');
        $pagerView->set('sort_options', FCom_CatalogIndex_Model_Field::i()->getSortingArray());

        $productsData = FCom_CatalogIndex_Indexer::i()->searchProducts($q, null, false);
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_orm', ['data' => $productsData['orm']]);
        $r = $req->get();
        #$r['sc'] = '';
        $paginated = $productsData['orm']->paginate($r, [
            'ps' => $pagerView->default_page_size,
            'page_size_options' => $pagerView->page_size_options,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
        ]);
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        #$productsData['state']['sc'] = $req->get('sc');
        BEvents::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', ['data' => &$productsData]);

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core_Main::i()->lastNav(true);

        $layout->view('header')->set('query', $q);
        $layout->view('breadcrumbs')->set('crumbs', ['home', ['label' => 'Search: ' . $q, 'active' => true]]);
        $layout->view('catalog/search')->set('query', $q);

        $rowsViewName = 'catalog/product/' . $pagerView->getViewAs();
        $rowsView = $layout->view($rowsViewName);
        $layout->hookView('main_products', $rowsViewName);
        $rowsView->products_data = $productsData;
        $rowsView->products = $productsData['rows'];
        $pagerView->state = $productsData['state'];
        $pagerView->setCanonicalPrevNext();

        FCom_Catalog_Model_SearchHistory::i()->addSearchHit($q, $productsData['state']['c']);

        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);
    }
}
