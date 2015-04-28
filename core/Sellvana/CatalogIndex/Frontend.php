<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogIndex_Frontend
 *
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 * @property Sellvana_CatalogIndex_Indexer $Sellvana_CatalogIndex_Indexer
 */

class Sellvana_CatalogIndex_Frontend extends BClass
{
    public function layoutSetSortOptions()
    {
        $sortOptions = $this->Sellvana_CatalogIndex_Model_Field->getSortingArray();
        $this->BLayout->view('catalog/product/pager')->set('sort_options', $sortOptions);
    }

    public function onCategoryProductsData($args)
    {
        $productsData = $this->Sellvana_CatalogIndex_Indexer->searchProducts(null, null, false, [
            'category' => $args['category'],
        ]);
        $productsOrm = $productsData['orm'];

        $this->BEvents->fire('Sellvana_Catalog_Frontend_Controller_Category::action_index:products_orm', ['orm' => $productsOrm]);

        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $this->BLayout->view('catalog/product/pager');

        $paginated = $productsOrm->paginate($this->BRequest->get(), [
            'ps' => $pagerView->default_page_size,
            'page_size_options' => $pagerView->page_size_options,
            'sc' => $pagerView->default_sort,
            'sort_options'  => $pagerView->sort_options,
        ]);
        $productsData['rows'] = $paginated['rows'];
        $productsData['state'] = $paginated['state'];

        $this->BLayout->view('catalog/category/sidebar')->set('products_data', $productsData);

        $args['data'] = $productsData;
    }

    public function onSearchProductsData($args)
    {
        /** @var Sellvana_Catalog_Frontend_View_Pager $pagerView */
        $pagerView = $this->BLayout->view('catalog/product/pager');

        $pagerView->set('sort_options', $this->Sellvana_CatalogIndex_Model_Field->getSortingArray());

        $productsData = $this->Sellvana_CatalogIndex_Indexer->searchProducts($args['query'], null, false);
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
        $this->BLayout->view('catalog/category/sidebar')->set('products_data', $productsData);

        $args['data'] = $productsData;
    }
}
