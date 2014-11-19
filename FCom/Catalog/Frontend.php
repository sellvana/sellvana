<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Catalog_Frontend
 * @property FCom_FrontendCP_Main $FCom_FrontendCP_Main
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 */
class FCom_Catalog_Frontend extends BClass
{
    public function bootstrap()
    {
        if (class_exists('FCom_FrontendCP_Main')) {
            $this->FCom_FrontendCP_Main
                ->addEntityHandler('product', 'FCom_Catalog_Frontend_ControlPanel::productEntityHandler')
                ->addEntityHandler('category', 'FCom_Catalog_Frontend_ControlPanel::categoryEntityHandler')
            ;
        }
    }

    public function getFeaturedProducts()
    {
        return $this->FCom_Catalog_Model_Product->orm()->where('is_featured', 1)->limit(6)->find_many();
    }

    public function getPopularProducts()
    {
        return $this->FCom_Catalog_Model_Product->orm()->where('is_popular', 1)->limit(6)->find_many();
    }

    public function getRecentlyViewedProducts()
    {
        return [];
    }

    public function onWorkflowCustomerAddsItemsCalcDetails($args)
    {
        $post = $args['post'];
        foreach ($args['items'] as &$item) {
            //TODO: move shopper fields handling from FCom_CustomField_Frontend
        }
        unset($item);
        return true;
    }
}
