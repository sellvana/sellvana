<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CatalogIndex_Frontend extends BClass
{
    public function layoutSetSortOptions()
    {
        $sortOptions = $this->FCom_CatalogIndex_Model_Field->getSortingArray();
        $this->BLayout->view('catalog/product/pager')->set('sort_options', $sortOptions);
    }
}
