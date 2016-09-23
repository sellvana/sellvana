<?php

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
 * @property Sellvana_Catalog_Model_ProductMedia $Sellvana_Catalog_Model_ProductMedia
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
        $urlPrefix = $this->BConfig->get('modules/Sellvana_Catalog/url_prefix');
        if ($urlPrefix) {
            $urlPrefix = trim($urlPrefix, '/');
            $catName = preg_replace('#^/?' . preg_quote($urlPrefix, '#') . '/?#', '', $catName);
        }
        /** @var Sellvana_Catalog_Model_Category $category */
        $category = $this->Sellvana_Catalog_Model_Category->load($catName, 'url_path');
        if (!$category) {
            $this->forward(false);
            return $this;
        }
        $this->BApp->set('current_page_type', 'category');
        $this->BApp->set('current_category', $category);

        $this->layout('/catalog/category');
        $layout = $this->BLayout;

        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $layout->getView('catalog/product/pager');

        $q = $this->BRequest->get('q');
        if (is_array($q)) {
            $q = join(' ', $q);
        }
        if ($q !== '' && !is_null($q)) {
            $alias = $this->Sellvana_Catalog_Model_SearchAlias->fetchSearchAlias($q);
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
        $this->BApp->set('current_query', $q);

        $productsData = null;
        $this->BEvents->fire(__METHOD__ . ':products_data', [
            'category' => $category,
            'query' => $q,
            'data' => &$productsData,
        ]);

        if (!$productsData) {
            $filter = $this->BRequest->get('f');
            $productsOrm = $this->Sellvana_Catalog_Model_Product->searchProductOrm($q, $filter, $category);

            $request = $this->BRequest->request();
            if (empty($request['sc']) && !empty($request['sort'])) {
                $request['sc'] = $request['sort'];
            }

            $this->BEvents->fire(__METHOD__ . ':products_orm', ['orm' => $productsOrm, 'request' => &$request]);

            $productsData = $productsOrm->paginate($request, [
                'ps' => $pagerView->default_page_size,
                'sc' => $pagerView->default_sort,
                'page_size_options' => $pagerView->page_size_options,
                'sort_options' => $pagerView->sort_options,
            ]);
            $layout->getView('catalog/product/pager')->set(['query' => $q, 'filters' => $filter]);
        }

        $this->Sellvana_Catalog_Model_ProductMedia->collectProductsImages($productsData['rows']);
        $this->Sellvana_Catalog_Model_ProductPrice->collectProductsPrices($productsData['rows']);
        $this->Sellvana_Catalog_Model_InventorySku->collectInventoryForProducts($productsData['rows']);

        $this->BEvents->fire(__METHOD__ . ':products_data_after', ['data' => &$productsData]);

        $this->BApp->set('products_data', $productsData);

        $this->FCom_Core_Main->lastNav(true);

        /** @var FCom_Core_View_Head $head */
        $head = $layout->getView('head');
        $crumbs = ['home'];
        $activeCatIds = [$category->id()];
        $rootCategoryId = $this->BConfig->get('modules/FCom_Frontend/nav_top/root_category');
        $hide = (bool)$rootCategoryId;
        foreach ($category->ascendants() as $c) {
            if ($hide) { // hide ascendants of the root category
                if ($c->id() == $rootCategoryId) {
                    $hide = false;
                }

                continue;
            }

            $nodeName = $c->getLangField('node_name');
            if ($nodeName) {
                $activeCatIds[] = $c->id();
                $crumbs[] = ['label' => $nodeName, 'href' => $c->url()];
                $head->addTitle($nodeName);
            }
        }
        $crumbs[] = ['label' => $category->getLangField('node_name'), 'active' => true];
        $category->set('is_active', 1);

        $head->addTitle($category->getLangField('node_name'));
        $layout->getView('breadcrumbs')->set('crumbs', $crumbs);
        $layout->getView('catalog/search')->set('query', $q);

        $rowsViewName = 'catalog/product/' . $pagerView->getViewAs();
        $layout->hookView('main_products', $rowsViewName);
        $rowsView = $layout->getView($rowsViewName);
        $rowsView->set([
            'category' => $category,
            'products_data' => $productsData,
            'products' => $productsData['rows'],
        ]);
        $pagerView->set('state', $productsData['state'])
            ->setCanonicalPrevNext();

        $layout->getView('catalog/nav')->set([
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
