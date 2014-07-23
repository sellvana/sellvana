<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Test_Unit_CartTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/CartTest.xml');
    }

    public function testAddEntry()
    {

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");

        $mCart = FCom_Sales_Model_Cart::i();
        $mCart->sessionCart();
        $mCart->addProduct(4, ['qty' => 2, 'price' => 5]);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_cart'), "Insert failed");
    }

    public function testAddCartItems()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");

        $cart = FCom_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(3, ['qty' => 2, 'price' => 5]);
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Update cart failed");
        $this->assertEquals(3, count($cart->items()), "Items count is not correct");
        $this->assertEquals(7, $cart->itemQty(), "Items count is not correct");
    }

    public function testAddCartItemsWithZeroProductId()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");

        $cart = FCom_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(0, ['qty' => 2, 'price' => 5]);
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Update cart failed");
        $this->assertEquals(3, count($cart->items()), "Items count is not correct");
        $this->assertEquals(7, $cart->itemQty(), "Items count is not correct");
    }

    public function testUpdateCartItems()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");

        $cart = FCom_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(2, ['qty' => 2, 'price' => 5]);
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Update cart failed");
        $this->assertEquals(2, count($cart->items()), "Update cart items failed");
        $this->assertEquals(7, $cart->itemQty(), "Update cart items failed");
    }

    public function testRemoveCartItem()
    {
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_cart_item'), "Pre-Condition");

        $cart = FCom_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->removeProduct(2);
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_item'), "Update cart failed");
        $this->assertEquals(1, count($cart->items()), "Update cart items failed");
        $this->assertEquals(4, $cart->itemQty(), "Update cart items failed");
    }

    public function testClearCart()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");

        $cart = FCom_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");

        foreach ($cart->items() as $item) {
            $cart->removeItem($item);
        }

        $this->assertEquals(0, count($cart->items()), "Items count is not correct");
        $this->assertEquals(0, $cart->itemQty(), "Update cart items failed");
    }

    public function testMergeCarts()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");

        $cart = FCom_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $cart->merge(2);
        $this->assertEquals(3, count($cart->items()), "Items count is not correct");

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_sales_cart'), "Update cart failed");
    }

    public function testResetSessionCart()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");
        $mCart = FCom_Sales_Model_Cart::i();
        $cart = $mCart->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");

        $reset = $mCart->load(2);
        $cart = $mCart->sessionCart(false, $reset);
        $this->assertEquals(1, count($cart->items()), "Reset failed");
        $this->assertEquals(2, $cart->id(), "Reset failed");
    }
}
