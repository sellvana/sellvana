<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Catalog_Frontend_Controller_Search
 *
 * @property FCom_Catalog_Model_Category $FCom_Catalog_Model_Category
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Catalog_Model_SearchAlias $FCom_Catalog_Model_SearchAlias
 * @property FCom_Catalog_Model_SearchHistory $FCom_Catalog_Model_SearchHistory
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class FCom_Catalog_Frontend_Controller_Search extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
        $q = $this->BRequest->get('q');
        $filter = $this->BRequest->get('f');

        $catName = $this->BRequest->param('category');
        if ($catName === '' || is_null($catName)) {
            $this->forward(false);
            return;
        }
        /** @var FCom_Catalog_Model_Category $category */
        $category = $this->FCom_Catalog_Model_Category->load($catName, 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }

        $this->layout('/catalog/category');
        $layout = $this->BLayout;
        /** @var FCom_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $layout->view('catalog/product/pager');

        $productsORM = $this->FCom_Catalog_Model_Product->searchProductOrm($q, $filter, $category);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_orm', ['orm' => $productsORM]);
        $productsData = $productsORM->paginate(null, [
            'ps' => $pagerView->default_page_size,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
            'page_size_options' => $pagerView->page_size_options,
        ]);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_category:products_data', ['data' => &$productsData]);

        $this->BApp->i()
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

        $layoutData = $category->getData('layout');
        if ($layoutData) {
            $context = ['type' => 'category', 'main_view' => 'catalog/category'];
            $layoutUpdate = $this->FCom_Core_LayoutEditor->compileLayout($layoutData, $context);
            if ($layoutUpdate) {
                $this->BLayout->addLayout('category_page', $layoutUpdate)->applyLayout('category_page');
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
            'home_url' => $this->BConfig->get('modules/FCom_Catalog/url_prefix'),
        ]);


        $this->FCom_Core_Main->lastNav(true);

    }

    public function action_search()
    {
        $q = $this->BRequest->get('q');
        if (is_array($q)) {
            $q = join(' ', $q);
        }
        $filter = $this->BRequest->get('f');

        $this->layout('/catalog/category');
        $layout = $this->BLayout;
        /** @var FCom_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $layout->view('catalog/product/pager');

        $q = $this->FCom_Catalog_Model_SearchAlias->processSearchQuery($q);

        $productsORM = $this->FCom_Catalog_Model_Product->searchProductOrm($q, $filter);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_orm', ['data' => $productsORM]);
        $productsData = $productsORM->paginate(null, [
            'ps' => $pagerView->default_page_size,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
            'page_size_options' => $pagerView->page_size_options,
        ]);
        $this->BEvents->fire('FCom_Catalog_Frontend_Controller_Search::action_search:products_data', ['data' => &$productsData]);

        $category = $this->FCom_Catalog_Model_Category->orm()->where_null('parent_id')->find_one();
        $this->BApp->i()
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

        $this->FCom_Catalog_Model_SearchHistory->addSearchHit($q, $productsData['state']['c']);

        $layout->view('header-top')->set('query', $q);
        $layout->view('breadcrumbs')->set('crumbs', ['home', ['label' => 'Search: ' . $q, 'active' => true]]);
        $layout->view('catalog/search')->set('query', $q);
        $pagerView->set('filters', $filter);
        $pagerView->set('query', $q);

        $this->FCom_Core_Main->lastNav(true);
    }


}
