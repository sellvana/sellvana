<?php

class FCom_Wishlist_Model_WishlistItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist_items';
    protected static $_origClass = __CLASS__;

    protected $product;

    public function product()
    {
        if ( !$this->product ) {
            $this->product = $this->relatedModel( 'FCom_Catalog_Model_Product', $this->product_id );
        }
        return $this->product;
    }
}