<?php
class FCom_Wishlist_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tWishlist = FCom_Wishlist_Model_Wishlist::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tWishlist} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `customer_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `customer_id` (`customer_id`)
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

    public function upgrade__0_1_0__0_1_1()
    {
        BDb::ddlTableDef(FCom_Wishlist_Model_Wishlist::table(), [
            'COLUMNS' => [
                'user_id' => 'RENAME customer_id int(10) unsigned not null',
            ],
        ]);
    }
}