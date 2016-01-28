<?php

/**
 * Class Sellvana_Wishlist_Migrate
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Wishlist_Model_Wishlist $Sellvana_Wishlist_Model_Wishlist
 * @property Sellvana_Wishlist_Model_WishlistItem $Sellvana_Wishlist_Model_WishlistItem
 */

class Sellvana_Wishlist_Migrate extends BClass
{
    public function install__0_5_1_0()
    {
        $tWishlist = $this->Sellvana_Wishlist_Model_Wishlist->table();
        $tWishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tWishlist, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'customer_id' => 'int unsigned default null',
                'cookie_token' => 'varchar(40) default null',
                'title' => "varchar(255) null",
                'is_default' => 'tinyint not null default 1',
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
                'product' => ['product_id', $tProduct],
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $this->BDb->ddlTableDef($this->Sellvana_Wishlist_Model_Wishlist->table(), [
            BDb::COLUMNS => [
                'user_id' => 'RENAME customer_id int(10) unsigned not null',
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tWishlist = $this->Sellvana_Wishlist_Model_Wishlist->table();
        $tWishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->Sellvana_Wishlist_Model_WishlistItem->delete_many("wishlist_id not in (select id from {$tWishlist})");

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
                'product' => ['product_id', $tProduct],
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tWishlist = $this->Sellvana_Wishlist_Model_Wishlist->table();
        $tWishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->table();
        $tProduct = $this->Sellvana_Catalog_Model_Product->table();

        $this->BDb->ddlTableDef($tWishlistItem, [
            BDb::CONSTRAINTS => [
                'wishlist' => ['wishlist_id', $tWishlist],
                'product' => ['product_id', $tProduct],
            ],
        ]);
    }

    public function upgrade__0_5_0_0__0_5_1_0()
    {
        $tWishlist = $this->Sellvana_Wishlist_Model_Wishlist->table();
        $this->BDb->ddlTableDef($tWishlist, [
            BDb::COLUMNS => [
                'title' => "varchar(255) null",
                'is_default' => 'tinyint not null default 1',
            ],
            BDb::KEYS => [
                'IDX_default' => '(customer_id, is_default)',
            ],
        ]);
    }

    public function upgrade__0_5_1_0__0_5_2_0()
    {
        $tWishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->table();
        $this->BDb->ddlTableDef($tWishlistItem, [
            BDb::COLUMNS => [
                'create_at' => 'datetime not null',
                'comment' => 'text',
            ],
        ]);
    }
}
