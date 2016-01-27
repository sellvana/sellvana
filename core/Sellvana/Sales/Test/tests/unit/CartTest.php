<?php

/**
 * Class Sellvana_Sales_Tests_Model_CartTest
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */

class CartTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Wishlist\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->initDataSet();
    }

    protected function _after()
    {
    }

    private function initDataSet()
    {
        $xml = simplexml_load_file(__DIR__ . '/CartTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->sessionCart(true);
        $cart->addProduct(4, ['qty' => 2, 'price' => 5]);

        $this->tester->seeNumRecords(3, 'fcom_sales_cart');
    }

    public function testAddCartItems()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(3, ['qty' => 2, 'price' => 5]);
        $items = Sellvana_Sales_Model_Cart_Item::i()->orm()->where('cart_id', 1)->find_many_assoc();
        $this->assertEquals(3, count($items), "Items count is not correct");
        $this->assertEquals(7, $cart->itemQty(), "Items count is not correct");

        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
    }

    public function testAddCartItemsWithZeroProductId()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(0, ['qty' => 2, 'price' => 5]);
        $this->assertEquals(3, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
    }

    public function testUpdateCartItems()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(2, ['qty' => 2, 'price' => 5]);
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
        $this->assertEquals(2, count($cart->items()), "Update cart items failed");
        $this->assertEquals(7, $cart->itemQty(), "Update cart items failed");
    }

    public function testRemoveCartItem()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_cart_item');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->removeProduct(2);
        $this->tester->seeNumRecords(2, 'fcom_sales_cart_item');
        $this->assertEquals(1, count($cart->items()), "Update cart items failed");
        $this->assertEquals(4, $cart->itemQty(), "Update cart items failed");
    }

    public function testClearCart()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");

        foreach ($cart->items() as $item) {
            $cart->removeItem($item);
        }

        $this->assertEquals(0, count($cart->items()), "Items count is not correct");
        $this->assertEquals(0, $cart->itemQty(), "Update cart items failed");
    }

    public function testMergeCarts()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $cart->merge(2);
        $this->assertEquals(3, count($cart->items()), "Items count is not correct");

        $this->tester->seeNumRecords(1, 'fcom_sales_cart');
    }

    public function testResetSessionCart()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");

        $reset = Sellvana_Sales_Model_Cart::i()->load(2);
        $cart = Sellvana_Sales_Model_Cart::i()->resetSessionCart($reset);
        $this->assertEquals(1, count($cart->items()), "Reset failed");
        $this->assertEquals(2, $cart->id(), "Reset failed");
    }
}
