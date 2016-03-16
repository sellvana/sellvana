<?php

/**
 * Class Sellvana_Sales_Tests_Model_CartTest
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
class CartTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Sales\UnitTester
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

    /**
     * Add products to session cart test
     */
    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
        /** @var Sellvana_Sales_Model_Cart $hlp */
        $hlp = Sellvana_Sales_Model_Cart::i();
        $cart = $hlp->sessionCart(true);
        $cart->addProduct(4, ['qty' => 2, 'price' => 5]);

        $this->tester->seeNumRecords(3, 'fcom_sales_cart');
    }

    /**
     * @throws BException
     */
    public function testAddCartItems()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->tester->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
    }

    public function testGetCartItems()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_cart_item');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $items = $cart->cartItems(1);

        $this->tester->assertEquals(2, count($items), 'Item count is not correct');

    }

    public function testTotalItemsInCart()
    {
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $cart->addProduct(3, ['qty' => 2, 'price' => 5]);
        $items = Sellvana_Sales_Model_Cart_Item::i()->orm()->where('cart_id', 1)->find_many();
        $this->tester->assertEquals(3, count($items), "Items count is not correct");
        $this->tester->assertEquals(7, intval($cart->itemQty()), "Items count is not correct");
    }

    public function testAddCartItemsWithZeroProductId()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->tester->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(0, ['qty' => 2, 'price' => 5]);
        $this->tester->assertEquals(3, count($cart->items()), "Items count is not correct");
        $this->tester->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
    }

    public function testUpdateCartItems()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->tester->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(2, ['qty' => 2, 'price' => 5]);
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
        $this->tester->assertEquals(2, count($cart->items()), "Update cart items failed");
        $this->tester->assertEquals(7, $cart->itemQty(), "Update cart items failed");
    }

    public function testRemoveCartItem()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_cart_item');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $item = $cart->addProduct(3, ['qty' => 3, 'price' => 5]);
        $this->tester->assertEquals(3, count($cart->items()), "Items count is not correct");
        $this->tester->assertEquals(8, $cart->itemQty(), "Items count is not correct");

        $cart->removeItem($item);
        $this->tester->seeNumRecords(3, 'fcom_sales_cart_item');
        $this->tester->assertEquals(2, count($cart->items()), "Update cart items failed");
        $this->tester->assertEquals(5, $cart->itemQty(), "Update cart items failed");
    }

    public function testRemoveCartItemByProductId()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_cart_item');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->tester->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->removeProduct(2);
        $this->tester->seeNumRecords(2, 'fcom_sales_cart_item');
        $this->tester->assertEquals(1, count($cart->items()), "Update cart items failed");
        $this->tester->assertEquals(4, $cart->itemQty(), "Update cart items failed");
    }

    public function testClearCart()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");

        foreach ($cart->items() as $item) {
            $cart->removeItem($item);
        }

        $this->tester->assertEquals(0, count($cart->items()), "Items count is not correct");
        $this->tester->assertEquals(0, $cart->itemQty(), "Update cart items failed");
    }

    public function testMergeCarts()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");
        $cart->merge(2);
        $this->tester->assertEquals(3, count($cart->items()), "Items count is not correct");

        $this->tester->seeNumRecords(1, 'fcom_sales_cart');
    }

    public function testResetSessionCart()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");

        $reset = Sellvana_Sales_Model_Cart::i()->load(2);
        $cart = Sellvana_Sales_Model_Cart::i();
        $cart->resetSessionCart($reset);
        $this->tester->assertEquals(1, count($cart->items()), "Reset failed");
        $this->tester->assertEquals(2, $cart->id(), "Reset failed");
    }

    public function testRecentItems()
    {
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");

        $items = $cart->items();
        $this->tester->assertEquals(2, count($items), "Items count is not correct");
        $cart->addProduct(3, ['qty' => 2, 'price' => 5]);

        $recentItem = $cart->recentItems();
        $this->tester->assertEquals(1, $recentItem, 'Recent item count is not correct');

        $this->tester->seeNumRecords(4, 'fcom_sales_cart_items', 'Items in cart do not corrext');
    }

    public function testLoadProducts()
    {
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(2);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");

        $items = $cart->items();
        $tmpItem = $items[0];
        $cart->loadProducts([$tmpItem]);

        $p = Sellvana_Catalog_Model_Product::i()->loadWhere(['id' => $tmpItem->get('product_id')]);
        $this->tester->assertSame($p, $tmpItem->getProduct(), 'Product loaded is not correct');

        $item = $cart->addProduct(1, ['qty' => 2, 'price' => 5]);
        $items = $cart->items();

        $this->tester->assertContains($item, $items, 'Cart does not contain added product');

        $cart->loadProducts($items);
        foreach ($items as $item) {
            $this->tester->assertFalse($item->getProduct(), 'Can not get product from cart item');
        }
        $this->tester->seeNumRecords(4, 'fcom_sales_cart_items', 'Items in cart are not corrext');
    }

    public function testGetTotals()
    {
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(2);
        $this->tester->assertEquals(2, count($cart->items()), "Items count is not correct");

        $totals = $cart->calculateTotals()->getTotals();
        $this->tester->assertEquals(2, $totals, 'Items count is not correct');

        $cart->addProduct(1);
        $totals = $cart->calculateTotals()->getTotals();
        $this->tester->assertEquals(3, $totals, 'Items count is not correct');
        $this->tester->canSeeInDatabase(Sellvana_Sales_Model_Cart_Item::table(), ['product_id' => 1, 'cart_id' => 2]);
    }
}
