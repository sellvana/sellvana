<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Wishlist_Model_WishlistItem
 *
 * @property int $id
 * @property int $wishlist_id
 * @property int $product_id
 */
class FCom_Wishlist_Model_WishlistItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist_items';
    protected static $_origClass = __CLASS__;

    protected $product;

    /**
     * get related product
     * @return FCom_Catalog_Model_Product
     */
    public function product()
    {
        if (!$this->product) {
            $this->product = $this->relatedModel('FCom_Catalog_Model_Product', $this->product_id);
        }
        return $this->product;
    }
}