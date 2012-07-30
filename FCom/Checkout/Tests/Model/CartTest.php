<?php

class FCom_Checkout_Tests_Model_CartTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/CartTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_cart'), "Pre-Condition");

        $cart = FCom_Checkout_Model_Cart::sessionCart();
        $cart->addProduct(4, array('qty' => 2, 'price'=>5));

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_cart'), "Insert failed");
    }

    public function testAddCartItems()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_cart'), "Pre-Condition");

        $cart = FCom_Checkout_Model_Cart::load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");

        $cart->addProduct(3, array('qty' => 2, 'price'=>5));
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_cart'), "Update cart failed");
        $this->assertEquals(3, count($cart->items()), "Items count is not correct");


    }
}