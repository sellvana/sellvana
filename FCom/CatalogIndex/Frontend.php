<?php

class FCom_CatalogIndex_Frontend extends BClass
{
    public function layoutSetSortOptions()
    {
        $sortOptions = FCom_CatalogIndex_Model_Field::i()->getSortingArray();
        BLayout::i()->view('catalog/product/pager')->set('sort_options', $sortOptions);
    }
}
