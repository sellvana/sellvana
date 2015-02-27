<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @class FCom_Promo_Model_PromoDisplay
 */
class FCom_Promo_Model_PromoDisplay extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_promo_display';
    protected static $_origClass = __CLASS__;

    protected static $_displayPagesLocations = null;

    public function collectDisplayPageLocations($reset = false)
    {
        if (static::$_displayPagesLocations && !$reset) {
            return $this;
        }

        $result = [
            'home' => [
                'label' => 'Home Page',
                'locations' => [
                    'above_page_content' => ['label' => 'Above Page Content'],
                    'under_page_content' => ['label' => 'Under Page Content'],
                ],
            ],
            'category' => [
                'label' => 'Category Page',
                'locations' => [
                    'above_thumbnail' => ['label' => 'Under Thumbnail'],
                    'under_thumbnail' => ['label' => 'Under Thumbnail'],
                    'under_product_name' => ['label' => 'Under Product Name'],
                    'above_add_to_cart_button' => ['label' => 'Above Add To Cart Block'],
                    'under_add_to_cart_button' => ['label' => 'Under Add To Cart Block'],
                ],
            ],
            'search' => [
                'label' => 'Search Page',
                'locations' => [
                    'above_thumbnail' => ['label' => 'Under Thumbnail'],
                    'under_thumbnail' => ['label' => 'Under Thumbnail'],
                    'under_product_name' => ['label' => 'Under Product Name'],
                    'above_add_to_cart_button' => ['label' => 'Above Add To Cart Block'],
                    'under_add_to_cart_button' => ['label' => 'Under Add To Cart Block'],
                ],
            ],
            'product' => [
                'label' => 'Product Page',
                'locations' => [
                    'above_product_name' => ['label' => 'Above Product Name'],
                    'under_product_name' => ['label' => 'Under Product Name'],
                    'under_add_to_cart_block' => ['label' => 'Under Add To Cart Block'],
                    'above_description_block' => ['label' => 'Above Description  Block'],
                    'above_add_to_cart_button' => ['label' => 'Above Add To Cart Button'],
                    'under_add_to_cart_button' => ['label' => 'Under Add To Cart Button'],
                ],
            ],
            'cart' => [
                'label' => 'Shopping Cart',
                'locations' => [
                    'above_cart_items' => ['label' => 'Above Cart Items'],
                    'under_cart_items' => ['label' => 'Under Cart Items'],
                    'above_xsell_items' => ['label' => 'Above Cross Sell Items'],
                    'under_xsell_items' => ['label' => 'Under Cross Sell Items'],
                    'above_checkout_button' => ['label' => 'Above Checkout Button'],
                    'under_checkout_button' => ['label' => 'Under Checkout Button'],
                ],
            ],
            'success' => [
                'label' => 'Success Page',
                'locations' => [
                    'above_page_content' => ['label' => 'Above Page Content'],
                    'under_page_content' => ['label' => 'Under Page Content'],
                ],
            ],
        ];

        $this->BEvents->fire(__METHOD__, ['result' => &$result]);

        static::$_displayPagesLocations = $result;

        return $this;
    }

    public function addDisplayPage($pageCode, array $data)
    {
        if (empty($data['label'])) {
            $data['label'] = $pageCode;
        }
        if (empty($data['locations'])) {
            $data['locations'] = [];
        }
        static::$_displayPagesLocations[$pageCode] = $data;
        return $this;
    }

    public function addDisplayPageLocation($pageCode, $locCode, $data)
    {
        if (!is_array($data)) {
            $data = ['label' => $data];
        }
        static::$_displayPagesLocations[$pageCode]['locations'][$locCode] = $data;
        return $this;
    }

    public function getDisplayPages()
    {
        $this->collectDisplayPageLocations();
        $options = [];
        foreach (static::$_displayPagesLocations as $pageCode => $data) {
            $options[$pageCode] = $data['label'];
        }
        return $options;
    }
    
    public function getDisplayPageLocations()
    {
        $this->collectDisplayPageLocations();
        $options = [];
        foreach (static::$_displayPagesLocations as $pageCode => $pageData) {
            foreach ($pageData['locations'] as $locCode => $locData) {
                $options[$pageCode][$locCode] = $locData['label'];
            }
        }
        return $options;
    }

    public function findDisplayBlocks($product, $locCode = null, $pageCode = null, $allProducts = null)
    {
        if (!$pageCode) {
            $pageCode = $this->BApp->get('current_page_type');
        }
        $result = [];

        return $result;
    }
}
