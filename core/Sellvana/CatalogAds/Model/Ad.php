<?php

/**
 * Class Sellvana_CatalogAds_Model_Ad
 *
 * @property Sellvana_CatalogAds_Model_AdCategory $Sellvana_CatalogAds_Model_AdCategory
 * @property Sellvana_CatalogAds_Model_AdTerm $Sellvana_CatalogAds_Model_AdTerm
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 */
class Sellvana_CatalogAds_Model_Ad extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_catalog_ad';
    static protected $_origClass = __CLASS__;

    public function findAdsMatchingRequest($viewType)
    {
        $q = $this->BApp->get('current_query');
        $q = $this->BUtil->simplifyString($q, '#[^a-z0-9]+#', ' ');
        $c = $this->BApp->get('current_category');

        if (!$q && !$c) {
            return [];
        }

        $orm = $this->orm('a')->order_by_asc('priority');
        $whereOr = [];
        if ($q) {
            $tAdTerm = $this->Sellvana_CatalogAds_Model_AdTerm->table();
            $terms = "'" . str_replace(' ', "','", $q) . "'";
            // safe because of simplifyString
            $whereOr[] = "a.id in (select ad_id from {$tAdTerm} at where at.term in ({$terms}))";
        }
        if ($c) {
            $tAdCategory = $this->Sellvana_CatalogAds_Model_AdCategory->table();
            $tCategory = $this->Sellvana_Catalog_Model_Category->table();
            $cId       = $c->id();
            $cIdPath   = $c->get('id_path');
            // safe because none comes from user input
            $whereOr[] = "a.id in (select ad_id from {$tAdCategory} ac join {$tCategory} c on c.id=ac.category_id 
                where ac.category_id={$cId} or (a.include_subcategories=1 and '{$cIdPath}' like concat(c.id_path, '/%')))";
        }
        $result = $orm->where_complex(['OR' => $whereOr])->find_many();
        return $result;
    }
    
    public function collectCategoriesAndTerms()
    {
        $categories = $this->Sellvana_CatalogAds_Model_AdCategory->orm('ac')
            ->where('ad_id', $this->id())
            ->join('Sellvana_Catalog_Model_Category', ['c.id', '=', 'ac.category_id'], 'c')
            ->find_many_assoc('category_id', 'full_name');

        $terms = $this->Sellvana_CatalogAds_Model_AdTerm->orm('at')
            ->where('ad_id', $this->id())
            ->find_many_assoc('term', 'term');

        $this->set([
            'category_ids' => join(',', array_keys($categories)),
            'terms' => join(',', array_keys($terms)),

            'selected_categories_data' => $this->BUtil->arrayMapToSeq($categories, 'id', 'text'),
            'selected_terms_data' => $this->BUtil->arrayMapToSeq($terms, 'id', 'text'),
        ]);

        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) {
            return false;
        }

        if (!$this->get('grid_cms_block_id')) {
            $this->set('grid_cms_block_id', null);
        }
        if (!$this->get('list_cms_block_id')) {
            $this->set('list_cms_block_id', null);
        }

        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        if ($this->get('category_ids') !== null) {
            $cIds = $this->BUtil->arrayCleanInt($this->get('category_ids'));
            $this->Sellvana_CatalogAds_Model_AdCategory->updateManyToManyIds($this, 'ad_id', 'category_id', $cIds);
        }

        if ($this->get('terms') !== null) {
            $terms = $this->BUtil->arrayCleanEmpty($this->get('terms'));
            $this->Sellvana_CatalogAds_Model_AdTerm->updateManyToManyIds($this, 'ad_id', 'term', $terms);
        }
    }
}