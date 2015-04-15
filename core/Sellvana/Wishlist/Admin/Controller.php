<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Wishlist_Admin_Controller
 *
 * @property Sellvana_Wishlist_Model_Wishlist $Sellvana_Wishlist_Model_Wishlist
 */
class Sellvana_Wishlist_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'wishlist';
    protected $_modelClass = 'Sellvana_Wishlist_Model_Wishlist';
    protected $_gridTitle = 'Wishlist';
    protected $_mainTableAlias = 'w';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        return $config;
    }

    /**
     * get grid config for wishlist of customer
     * @param $customer Sellvana_Customer_Model_Customer
     * @return array
     */
    public function customerWishlistGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_grid_wishlist_' . $customer->id;
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'wishlist_id', 'label' => 'Wishlist ID'],
            ['name' => 'product_name', 'label' => 'Product Name'],
            ['name' => 'product_sku', 'label' => 'SKU'],
            ['name' => 'base_price', 'label' => 'Base Price'],
            ['name' => 'sale_price', 'label' => 'Sale Price'],
        ];

        $data = [];
        /** @var Sellvana_Wishlist_Model_Wishlist[] $wishlistArr */
        $wishlistArr = $this->Sellvana_Wishlist_Model_Wishlist->orm()->where('customer_id', $customer->id)->find_many();
        if ($wishlistArr) {
            foreach ($wishlistArr as $wishlist) {
                /** @var Sellvana_Wishlist_Model_WishlistItem[] $items */
                $items = $wishlist->items();
                if ($items) {
                    foreach ($items as $item) {
                        $arr = $item->getProduct()->as_array();
                        $arr['wishlist_id'] = $wishlist->id;
                        $data[] = $arr;
                    }
                }
            }
        }
        $config['data'] = $data;
        $config['data_mode'] = 'local';
        unset($config['orm']);
        return ['config' => $config];
    }

}
