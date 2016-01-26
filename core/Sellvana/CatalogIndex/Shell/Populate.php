<?php

/**
 * Class Sellvana_CatalogIndex_Shell_Populate
 *
 * @property Sellvana_CatalogIndex_Model_Doc $Sellvana_CatalogIndex_Model_Doc
 * @property Sellvana_CatalogIndex_Main $Sellvana_CatalogIndex_Main
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_CatalogIndex_Shell_Populate extends FCom_Shell_Action_Abstract
{
    static protected $_origClass = __CLASS__;

    static protected $_actionName = 'catalog:populate';

    static protected $_availOptions = [
        'c?' => 'categories',
        's?' => 'subcategories',
        'p?' => 'products',
        'r?' => 'reindex',
        //'d' => 'delete-all',
    ];

    protected function _run()
    {
        $this->BDebug->disableAllLogging();
        $this->Sellvana_CatalogIndex_Main->autoReindex(false);
        $this->Sellvana_Catalog_Model_Product->setFlag('skip_duplicate_checks', true);

        $this->println('Starting populating catalog with random sample data...');

        $params = [];
        foreach (['c', 's', 'p', 'r'] as $o) {
            $v = $this->getOption($o);
            $params[$o] = is_array($v) ? $v[0] : $v;
        }
        $this->Sellvana_CatalogIndex_Main->generateTestData($params);

        $this->println('Populate complete');
    }

    public function getShortHelp()
    {
        return 'Populate catalog with random test sample data';
    }

    public function getLongHelp()
    {
        return <<<EOT

Populate catalog with random test sample data

Options:
    {white*}-c {green*}[cnt]{white*}
    --categories={green*}[cnt]{/}    Generate top nav categories (default: {white*}9{/})
                             {green*}0{/} - don't generate categories
    
    {white*}-s {green*}[cnt]{white*}
    --subcategories={green*}[cnt]{/} Generate subcategories (default: {white*}10{/} for each category)
                             {green*}0{/} - don't generate subcategories
    
    {white*}-p {green*}[cnt]{white*}
    --products={green*}[cnt]{/}      Generate products (default: {white*}1000{/})
                             {green*}0{/} - don't generate products

    {white*}-r {green*}[cnt]{white*}
    --reindex={green*}[flag]{/}      Reindex on completion (default: 1)
                             {green*}0{/} - don't reindex
                             {green*}1{/} - reindex only new products
                             {green*}2{/} - reindex whole catalog

EOT;
    }
}