<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CatalogIndex_Frontend
 *
 * @property Sellvana_CatalogIndex_Model_Field $Sellvana_CatalogIndex_Model_Field
 */

class Sellvana_CatalogIndex_Frontend extends BClass
{
    public function layoutSetSortOptions()
    {
        $sortOptions = $this->Sellvana_CatalogIndex_Model_Field->getSortingArray();
        $this->BLayout->view('catalog/product/pager')->set('sort_options', $sortOptions);
    }
}
