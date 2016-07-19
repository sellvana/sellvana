<?php

/**
 * Class Sellvana_CatalogIndex_Frontend
 *
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 */

class Sellvana_CatalogIndex_Frontend extends BClass
{
    public function layoutSetSortOptions()
    {
        $sortOptions = $this->Sellvana_CatalogIndex_Model_Field->getSortingArray();
        $this->BLayout->getView('catalog/product/pager')->set('sort_options', $sortOptions);
    }

    public function onCategoryProductsData($args)
    {
        $productsData = $this->Sellvana_CatalogIndex_Main->getIndexer()->searchProducts([
            'sort' => $this->BRequest->get('sort'),
            'options' => ['category' => $args['category']]
        ]);
        $productsOrm = $productsData['orm'];

        $this->BEvents->fire('Sellvana_Catalog_Frontend_Controller_Category::action_index:products_orm', ['orm' => $productsOrm]);

        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $this->BLayout->getView('catalog/product/pager');

        $paginated = $productsOrm->paginate($this->BRequest->get(), [
            'ps' => $pagerView->default_page_size,
            'page_size_options' => $pagerView->page_size_options,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
        ]);
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];

        $this->BLayout->getView('catalog/category/sidebar')->set('products_data', $productsData);

        $args['data'] = $productsData;
    }

    public function onSearchProductsData($args)
    {
        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $this->BLayout->getView('catalog/product/pager');

        $pagerView->set('sort_options', $this->Sellvana_CatalogIndex_Model_Field->getSortingArray());

        $productsData = $this->Sellvana_CatalogIndex_Main->getIndexer()->searchProducts([
            'query' => $args['query'],
            'sort' => $this->BRequest->get('sort'),
        ]);
        $productsOrm = $productsData['orm'];
        $this->BEvents->fire('Sellvana_Catalog_Frontend_Controller_Search::action_index:products_orm', ['orm' => $productsOrm]);

        $paginated = $productsOrm->paginate($this->BRequest->get(), [
            'ps' => $pagerView->default_page_size,
            'page_size_options' => $pagerView->page_size_options,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
        ]);
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];

        $this->BLayout->getView('catalog/category/sidebar')->set('products_data', $productsData);

        $args['data'] = $productsData;
    }
}
