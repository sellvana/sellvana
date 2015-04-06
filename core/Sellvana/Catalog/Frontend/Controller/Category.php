<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Frontend_Controller_Category
 *
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_SearchAlias $Sellvana_Catalog_Model_SearchAlias
 * @property Sellvana_Catalog_Model_SearchHistory $Sellvana_Catalog_Model_SearchHistory
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */

class Sellvana_Catalog_Frontend_Controller_Category extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $catName = $this->BRequest->param('category');
        if ($catName === '' || is_null($catName)) {
            $this->forward(false);
            return $this;
        }
        /** @var Sellvana_Catalog_Model_Category $category */
        $category = $this->Sellvana_Catalog_Model_Category->load($catName, 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }

        $this->BApp->set('current_page_type', 'category');

        $this->layout('/catalog/category');
        $layout = $this->BLayout;

        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $layout->view('catalog/product/pager');

        $q = $this->BRequest->get('q');
        if (is_array($q)) {
            $q = join(' ', $q);
        }
        if ($q !== '' && !is_null($q)) {
            $q = $this->Sellvana_Catalog_Model_SearchAlias->processSearchQuery($q);
        }

        $productsData = null;
        $this->BEvents->fire(__METHOD__ . ':products_data', [
            'category' => $category,
            'query' => $q,
            'data' => &$productsData,
        ]);

        if (!$productsData) {
            $filter = $this->BRequest->get('f');
            $productsOrm = $this->Sellvana_Catalog_Model_Product->searchProductOrm($q, $filter, $category);
            $this->BEvents->fire(__METHOD__ . ':products_orm', ['orm' => $productsOrm]);

            $productsData = $productsOrm->paginate($this->BRequest->get(), [
                'ps' => $pagerView->default_page_size,
                'sc' => $pagerView->default_sort,
                'page_size_options' => $pagerView->page_size_options,
                'sort_options' => $pagerView->sort_options,
            ]);
            $layout->view('catalog/product/pager')->set(['query' => $q, 'filters' => $filter]);
        }

        $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($productsData['rows']);
        $this->Sellvana_Catalog_Model_InventorySku->collectInventoryForProducts($productsData['rows']);

        $this->BEvents->fire(__METHOD__ . ':products_data_after', ['data' => &$productsData]);

        $this->BApp
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        $this->FCom_Core_Main->lastNav(true);

        /** @var FCom_Core_View_Head $head */
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
        $layout->hookView('main_products', $rowsViewName);
        $rowsView = $layout->view($rowsViewName);
        $rowsView->set([
            'category' => $category,
            'products_data' => $productsData,
            'products' => $productsData['rows'],
        ]);
        $pagerView->set('state', $productsData['state'])
            ->setCanonicalPrevNext();

        $layout->view('catalog/nav')->set([
            'category' => $category,
            'active_ids' => $activeCatIds,
            'home_url' => $this->BConfig->get('modules/Sellvana_Catalog/url_prefix'),
        ]);

        $layoutData = $category->getData('layout');
        if ($layoutData) {
            $context = ['type' => 'category', 'main_view' => 'catalog/category'];
            $layoutUpdate = $this->FCom_Core_LayoutEditor->compileLayout($layoutData, $context);
            if ($layoutUpdate) {
                $this->BLayout->addLayout('category_page', $layoutUpdate)->applyLayout('category_page');
            }
        }

        $this->FCom_Core_Main->lastNav(true);
    }
}
