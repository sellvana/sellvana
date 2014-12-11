<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CatalogIndex_Frontend
 *
 * @property FCom_CatalogIndex_Model_Field $FCom_CatalogIndex_Model_Field
 */

class FCom_CatalogIndex_Frontend extends BClass
{
    public function layoutSetSortOptions()
    {
        $sortOptions = $this->FCom_CatalogIndex_Model_Field->getSortingArray();
        $this->BLayout->view('catalog/product/pager')->set('sort_options', $sortOptions);
    }
}
