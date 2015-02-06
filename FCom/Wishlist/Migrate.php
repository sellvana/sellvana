<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Wishlist_Migrate
 *
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Wishlist_Model_Wishlist $FCom_Wishlist_Model_Wishlist
 * @property FCom_Wishlist_Model_WishlistItem $FCom_Wishlist_Model_WishlistItem
 */

class FCom_Wishlist_Migrate extends BClass
{
    public function install__0_1_2()
    {
        $tWishlist = $this->FCom_Wishlist_Model_Wishlist->table();
        $tWishlistItem = $this->FCom_Wishlist_Model_WishlistItem->table();
        $tCustomer = $this->FCom_Customer_Model_Customer->table();
        $tProduct = $this->FCom_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tWishlist, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_id' => 'int unsigned default null',
                'cookie_token' => 'varchar(40) default null',
                'remote_ip' => 'varchar(50) default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_customer_id' => '(customer_id)',
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
            ],
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
            ],
        ]);

        $this->BDb->ddlTableDef($tWishlistItem, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'wishlist_id' => 'int unsigned not null',
                'product_id' => 'int unsigned not null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'wishlist' => ['wishlist_id', $tWishlist],
                'product' => ['wishlist_id', $tProduct],
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $this->BDb->ddlTableDef($this->FCom_Wishlist_Model_Wishlist->table(), [
            BDb::COLUMNS => [
                'user_id' => 'RENAME customer_id int(10) unsigned not null',
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tWishlist = $this->FCom_Wishlist_Model_Wishlist->table();
        $tWishlistItem = $this->FCom_Wishlist_Model_WishlistItem->table();
        $tCustomer = $this->FCom_Customer_Model_Customer->table();
        $tProduct = $this->FCom_Catalog_Model_Product->table();

        $this->FCom_Wishlist_Model_WishlistItem->delete_many("wishlist_id not in (select id from {$tWishlist})");

        $this->BDb->ddlTableDef($tWishlist, [
            BDb::COLUMNS => [
                'customer_id' => 'int unsigned default null',
                'cookie_token' => 'varchar(40) default null',
                'remote_ip' => 'varchar(50) default null',
                'create_at' => 'datetime not null',
                'update_at' => 'datetime default null',
            ],
            BDb::KEYS => [
                'IDX_customer_id' => '(customer_id)',
                'UNQ_cookie_token' => 'UNIQUE (cookie_token)',
            ],
            BDb::CONSTRAINTS => [
                'customer' => ['customer_id', $tCustomer],
            ],
        ]);

        $this->BDb->ddlTableDef($tWishlistItem, [
            BDb::CONSTRAINTS => [
                'wishlist' => ['wishlist_id', $tWishlist],
                'product' => ['wishlist_id', $tProduct],
            ],
        ]);

    }
}
