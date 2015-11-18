<?php
namespace Wishlist\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Factory extends \Codeception\Module
{
	/**
     * @var \League\FactoryMuffin\Factory
     */
    protected $factory;
    
    public function _initialize()
    {
        $this->factory = new \League\FactoryMuffin\Factory;
        $this->factory->define('Sellvana_Customer_Model_Customer', [
            ['id' => 1, 'email' => 'test1@test.com', 'firstname' => 'Test 1'],
            ['id' => 2, 'email' => 'test2@test.com', 'firstname' => 'Test 2'],
            ['id' => 3, 'email' => 'test3@test.com', 'firstname' => 'Test 3'],
        ]);

        $this->factory->define('Sellvana_Catalog_Model_Product', [
			['id' => 1, 'product_name' => 'Product 1', 'product_sku' => 'product1', 'url_key' => 'product-1', 'base_price' => 2],
			['id' => 2, 'product_name' => 'Product 2', 'product_sku' => 'product2', 'url_key' => 'product-2', 'base_price' => 2],
			['id' => 3, 'product_name' => 'Product 3', 'product_sku' => 'product3', 'url_key' => 'product-3', 'base_price' => 3],
			['id' => 4, 'product_name' => 'Product 4', 'product_sku' => 'product4', 'url_key' => 'product-4', 'base_price' => 5],
		]);

		$this->factory->define('Sellvana_Wishlist_Model_Wishlist', [
			['id' => 1, 'customer_id' => 1],
			['id' => 2, 'customer_id' => 2],
		]);

		$this->factory->define('Sellvana_Wishlist_Model_WishlistItem', [
			['id' => 1, 'wishlist_id' => 1, 'product_id' => 1],
			['id' => 2, 'wishlist_id' => 1, 'product_id' => 2],
			['id' => 3, 'wishlist_id' => 2, 'product_id' => 3]
		]);
    }
}
