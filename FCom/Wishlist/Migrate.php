<?php
class FCom_Wishlist_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
    }
    public function install()
    {
        $tWishlist = FCom_Wishlist_Model_Wishlist::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tWishlist} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tWishlistItem = FCom_Wishlist_Model_WishlistItem::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tWishlistItem} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `wishlist_id` int(10) unsigned NOT NULL,
            `product_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `wishlist_id` (`wishlist_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");    
    }
}