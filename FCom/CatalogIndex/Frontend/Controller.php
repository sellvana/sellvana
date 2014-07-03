<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CatalogIndex_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
#echo "<pre>"; debug_print_backtrace(); print_r($this->BRouting->currentRoute()); exit;
        $catName = $this->BRequest->params('category');
        if ($catName === '' || is_null($catName)) {
            $this->forward(false);
            return $this;
        }
        $category = $this->FCom_Catalog_Model_Category->load($catName, 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }

        $this->layout('/catalog/category');
        $layout = $this->BLayout;
        $pagerView = $layout->view('catalog/product/pager');

        $q = $this->BRequest->get('q');

        $productsData = $this->FCom_CatalogIndex_Indexer->searchProducts(null, null, false, [
            'category' => $category,
        ]);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_orm', ['orm' => $productsData['orm']]);
        $r = $this->BRequest->get();
        $paginated = $productsData['orm']->paginate($r, [
            'ps' => $pagerView->default_page_size,
            'page_size_options' => $pagerView->page_size_options,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
        ]);
        //$paginated['state']['sc'] = $this->BRequest->get('sc');
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];
        //$productsData['state']['sc'] = $this->BRequest->get('sc');
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', ['data' => &$productsData]);

        $this->BApp->i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        $this->FCom_Core_Main->lastNav(true);

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
            'home_url' => $this->BConfig->get('modules/FCom_Catalog/url_prefix'),
        ]);

        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);

        if ($category->layout_update) {
            $layoutUpdate = $this->BYAML->parse($category->layout_update);
            if (!is_null($layoutUpdate)) {
                $this->BLayout->addLayout('category_page', $layoutUpdate)->applyLayout('category_page');
            } else {
                $this->BDebug->warning('Invalid layout update for CMS page');
            }
        }
    }

    public function action_search()
    {
        $req = $this->BRequest;
        $q = $req->get('q');
        if (is_array($q)) {
            $q = join(' ', $q);
        }
        if ($q !== '' && !is_null($q)) {
            $q = $this->FCom_Catalog_Model_SearchAlias->processSearchQuery($q);
        }

        $this->layout('/catalog/search');
        $layout = $this->BLayout;
        $pagerView = $layout->view('catalog/product/pager');
        $pagerView->set('sort_options', $this->FCom_CatalogIndex_Model_Field->getSortingArray());

        $productsData = $this->FCom_CatalogIndex_Indexer->searchProducts($q, null, false);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_orm', ['data' => $productsData['orm']]);
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
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', ['data' => &$productsData]);

        $this->BApp->i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        $this->FCom_Core_Main->lastNav(true);

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

        $this->FCom_Catalog_Model_SearchHistory->addSearchHit($q, $productsData['state']['c']);
        if ($this->BModuleRegistry->isLoaded('FCom_AdminLiveFeed')
            && $this->BConfig->get('modules/FCom_AdminLiveFeed/enable_catalog')
        ) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                    'text' => $this->_('The term %s has been searched', $q),
                ]);
        }
        $layout->view('catalog/category/sidebar')->set('products_data', $productsData);
    }
}
