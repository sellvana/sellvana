<?php

class FCom_Catalog_Frontend extends BClass
{
    static public function bootstrap()
    {
        if (class_exists('FCom_FrontendCP_Main')) {
            FCom_FrontendCP_Main::i()
                ->addEntityHandler('product', 'FCom_Catalog_Frontend_ControlPanel::productEntityHandler')
                ->addEntityHandler('category', 'FCom_Catalog_Frontend_ControlPanel::categoryEntityHandler')
            ;
        }
    }

    public function getFeaturedProducts()
    {
        return FCom_Catalog_Model_Product::i()->orm()->where('is_featured', 1)->limit(6)->find_many();
    }

    public function getPopularProducts()
    {
        return FCom_Catalog_Model_Product::i()->orm()->where('is_popular', 1)->limit(6)->find_many();
    }

    public function getRecentlyViewedProducts()
    {
        return [];
    }
}
