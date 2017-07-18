<?php

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
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
 * @property Sellvana_Catalog_Model_SearchHistoryLog $Sellvana_Catalog_Model_SearchHistoryLog
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
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
            $alias = $this->Sellvana_Catalog_Model_SearchAlias->fetchSearchAlias($q);
            if ($alias) {
                $targetUrl = $alias->get('target_url');
                if ($alias->get('alias_type') === Sellvana_Catalog_Model_SearchAlias::TYPE_FULL && $targetUrl) {
                    if (!$this->BUtil->isUrlFull($targetUrl)) {
                        $targetUrl = $this->BApp->href($targetUrl);
                    }
                    $this->BResponse->redirect($targetUrl);
                    return;
                } else {
                    $q = $alias->get('target_term');
                }
            }
        }

        $this->BApp->set('current_query', $q);

        $this->BEvents->fire(__METHOD__ . ':search_query', ['query' => &$q]);
        $this->layout('/catalog/search');
        $layout = $this->BLayout;

        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $layout->getView('catalog/product/pager');

        $productsData = null;
        $this->BEvents->fire(__METHOD__ . ':products_data', ['data' => &$productsData, 'query' => $q]);

        $this->BApp->set('products_data', $productsData);
        
        if (!$productsData) {
            $filter = $this->BRequest->get('f');
            $productsORM = $this->Sellvana_Catalog_Model_Product->searchProductOrm($q, $filter);
            $request = $this->BRequest->request();
            if (empty($request['sc']) && !empty($request['sort'])) {
                $request['sc'] = $request['sort'];
            }
            $this->BEvents->fire(__METHOD__ . ':products_orm', ['orm' => $productsORM, 'request' => &$request]);
            $productsData = $productsORM->paginate($request, [
                'ps' => $pagerView->default_page_size,
                'sc' => $pagerView->default_sort,
                'page_size_options' => $pagerView->page_size_options,
                'sort_options' => $pagerView->sort_options,
            ]);
        }

        if ($q) {
            $history = $this->Sellvana_Catalog_Model_SearchHistory->addSearchHit($q, $productsData['state']['c']);
            if ($history) {
                $this->Sellvana_Catalog_Model_SearchHistoryLog->addSearchHit($history->id());
            }
        }

        $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages($productsData['rows']);
        $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($productsData['rows']);
        $this->Sellvana_Catalog_Model_InventorySku->collectInventoryForProducts($productsData['rows']);

        $this->BEvents->fire(__METHOD__ . ':products_data_after', ['data' => &$productsData]);

        #$category = $this->Sellvana_Catalog_Model_Category->orm()->where_null('parent_id')->find_one();
        #$this->BApp->set('current_category', $category)
        #$this->BApp->set('products_data', $productsData);

        $rowsViewName = 'catalog/product/' . $pagerView->getViewAs();
        $layout->hookView('main_products', $rowsViewName);
        $rowsView = $layout->getView($rowsViewName);
        $rowsView->set([
            'products_data' => $productsData,
            'products' => $productsData['rows'],
        ]);
        $pagerView->set(['state' => $productsData['state'], 'query' => $q])
            ->setCanonicalPrevNext();

        $layout->getView('header-top')->set('query', $q);
        $layout->getView('breadcrumbs')->set('crumbs', ['home', ['label' => $this->_((('Search: %s')), $q), 'active' => true]]);
        $layout->getView('catalog/search')->set('query', $q)->set('data', $productsData);

        $this->FCom_Core_Main->lastNav(true);
    }

    public function action_autocomplete()
    {
        $products = [];
        $q = $this->BRequest->get('q');
        $alias = $this->Sellvana_Catalog_Model_SearchAlias->fetchSearchAlias($q);
        if ($alias) {
            $q = $alias->get('target_term');
        }
        if ($this->BModuleRegistry->isLoaded('Sellvana_CatalogIndex')) {
            $indexResult = $this->Sellvana_CatalogIndex_Main->getIndexer()->searchProducts(['query' => $q]);
            $orm = $indexResult['orm'];
        }  else {
            $orm = $this->Sellvana_Catalog_Model_Product->searchProductOrm($q);
        }
        if (!empty($orm)) {
            $products = $orm->select(['p.id', 'p.product_name', 'p.thumb_url', 'p.avg_rating', 'p.description',
                    'p.short_description', 'p.url_key'])
                ->limit(10)
                ->find_many();

            if (!empty($products)) {
                array_walk($products, function($product) {
                    if (empty($product->thumb_url)) {
                        $product->thumb_url = $product->thumbUrl(140);
                    }
                    $product->price = $product->getFrontendPrices();
                });
            }
        }

        $result = $this->BDb->many_as_array($products);
        $this->BResponse->json($result);
    }
}
