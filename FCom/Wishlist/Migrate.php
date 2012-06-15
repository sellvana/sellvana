<?php
class FCom_Wishlist_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }
    public function install()
    {
        FCom_Wishlist_Model_Wishlist::install();
        FCom_Wishlist_Model_WishlistItem::install();
    }
}