<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Frontend
 * @property Sellvana_FrontendCP_Main $Sellvana_FrontendCP_Main
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_Catalog_Frontend extends BClass
{
    public function bootstrap()
    {
        if (class_exists('Sellvana_FrontendCP_Main')) {
            $this->Sellvana_FrontendCP_Main
                ->addEntityHandler('product', 'Sellvana_Catalog_Frontend_ControlPanel::productEntityHandler')
                ->addEntityHandler('category', 'Sellvana_Catalog_Frontend_ControlPanel::categoryEntityHandler')
            ;
        }
    }

    public function getFeaturedProducts($cnt = null)
    {
        if (!$cnt) {
            $cnt = 6;
        }
        return $this->Sellvana_Catalog_Model_Product->orm()->where('is_featured', 1)->limit($cnt)->find_many();
    }

    public function getPopularProducts($cnt = null)
    {
        if (!$cnt) {
            $cnt = 6;
        }
        return $this->Sellvana_Catalog_Model_Product->orm()->where('is_popular', 1)->limit($cnt)->find_many();
    }

    public function getRecentlyViewedProducts()
    {
        return [];
    }
}
