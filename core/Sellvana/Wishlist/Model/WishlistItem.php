<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Wishlist_Model_WishlistItem
 *
 * @property int $id
 * @property int $wishlist_id
 * @property int $product_id
 */
class Sellvana_Wishlist_Model_WishlistItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist_items';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['wishlist_id', 'product_id'],
        'related'    => [
            'wishlist_id' => 'Sellvana_Wishlist_Model_Wishlist.id',
            'product_id'  => 'Sellvana_Catalog_Model_Product.id'
        ],
    ];

    protected $_product;

    /**
     * get related product
     * @return Sellvana_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->relatedModel('Sellvana_Catalog_Model_Product', $this->get('product_id'));
        }
        return $this->_product;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_product);
    }
}
