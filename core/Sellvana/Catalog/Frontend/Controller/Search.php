<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Frontend_Controller_Search
 *
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Catalog_Model_SearchAlias $Sellvana_Catalog_Model_SearchAlias
 * @property Sellvana_Catalog_Model_SearchHistory $Sellvana_Catalog_Model_SearchHistory
 * @property FCom_Core_Main $FCom_Core_Main
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_Catalog_Model_InventorySku $Sellvana_Catalog_Model_InventorySku
 * @property Sellvana_Catalog_Model_ProductPrice $Sellvana_Catalog_Model_ProductPrice
 */

class Sellvana_Catalog_Frontend_Controller_Search extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->BApp->set('current_page_type', 'search');

        $req = $this->BRequest;
        $q = $req->get('q');

        if (is_array($q)) {
            $q = join(' ', $q);
        }
        if ($q !== '' && !is_null($q)) {
            $q = $this->Sellvana_Catalog_Model_SearchAlias->processSearchQuery($q);
        }

        $this->BEvents->fire(__METHOD__ . ':search_query', ['query' => &$q]);
        $this->layout('/catalog/search');
        $layout = $this->BLayout;

        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $layout->view('catalog/product/pager');

        $productsData = null;
        $this->BEvents->fire(__METHOD__ . ':products_data', ['data' => &$productsData, 'query' => $q]);

        if (!$productsData) {
            $filter = $this->BRequest->get('f');
            $productsORM = $this->Sellvana_Catalog_Model_Product->searchProductOrm($q, $filter);
            $this->BEvents->fire(__METHOD__ . ':products_orm', ['orm' => $productsORM]);
            $productsData = $productsORM->paginate(null, [
                'ps' => $pagerView->default_page_size,
                'sc' => $pagerView->default_sort,
                'page_size_options' => $pagerView->page_size_options,
                'sort_options' => $pagerView->sort_options,
            ]);
        }

        $this->Sellvana_Catalog_Model_SearchHistory->addSearchHit($q, $productsData['state']['c']);

        $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($productsData['rows']);
        $this->Sellvana_Catalog_Model_InventorySku->collectInventoryForProducts($productsData['rows']);

        $this->BEvents->fire(__METHOD__ . ':products_data_after', ['data' => &$productsData]);

        $this->BApp->set('current_query', $q);
        #$category = $this->Sellvana_Catalog_Model_Category->orm()->where_null('parent_id')->find_one();
        #$this->BApp->set('current_category', $category)
        #$this->BApp->set('products_data', $productsData);

        $rowsViewName = 'catalog/product/' . $pagerView->getViewAs();
        $layout->hookView('main_products', $rowsViewName);
        $rowsView = $layout->view($rowsViewName);
        $rowsView->set([
            'products_data' => $productsData,
            'products' => $productsData['rows'],
        ]);
        $pagerView->set(['state' => $productsData['state'], 'query' => $q])
            ->setCanonicalPrevNext();

        $layout->view('header-top')->set('query', $q);
        $layout->view('breadcrumbs')->set('crumbs', ['home', ['label' => 'Search: ' . $q, 'active' => true]]);
        $layout->view('catalog/search')->set('query', $q);

        $this->FCom_Core_Main->lastNav(true);
    }
}
