<?php

/**
 * Class Sellvana_CatalogAds_Frontend
 *
 * @property Sellvana_CatalogAds_Model_Ad $Sellvana_CatalogAds_Model_Ad
 */
class Sellvana_CatalogAds_Frontend extends BClass
{
    /**
     * @var string
     */
    protected $_viewType;

    /**
     * @var Sellvana_CatalogAds_Model_Ad[]
     */
    protected $_matchingAds;

    /**
     * Sellvana_Catalog_Frontend_Controller_Category::action_index:products_orm
     * Sellvana_Catalog_Frontend_Controller_Search::action_index:products_orm
     *
     * @param array $args
     */
    public function onCatalogCategorySearchOrm($args)
    {
        $this->_viewType = $this->BLayout->getView('catalog/product/pager')->getViewAs();
        $this->_matchingAds = $this->Sellvana_CatalogAds_Model_Ad->findAdsMatchingRequest($this->_viewType);
        if (!$this->_matchingAds) {
            return;
        }
        $orm = $args['orm'];
        $request =& $args['request'];
        //TODO: implement adjusting limit and offset based on matching ads
    }

    /**
     * Sellvana_Catalog_Frontend_Controller_Category::action_index:products_data_after
     * Sellvana_Catalog_Frontend_Controller_Search::action_index:products_data_after
     *
     * @param array $args
     */
    public function onCatalogCategorySearchDataAfter($args)
    {
        if (!$this->_matchingAds) {
            return;
        }
        $rows =& $args['data']['rows'];
        if (!$rows) {
            return;
        }
        $ads = [];
        foreach ($this->_matchingAds as $ad) {
            $pos = $ad->get($this->_viewType . '_position') - 1;
            if (empty($ads[$pos])) {
                $ads[$pos] = $ad;
            }
        }
        foreach ($ads as $ad) {
            $ad->set('custom_' . $this->_viewType . '_view', 'catalog/product/cms-tile');
            array_splice($rows, $pos, 0, [$ad]);
        }
    }
}