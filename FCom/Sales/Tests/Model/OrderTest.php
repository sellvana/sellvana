<?php

class FCom_Sales_Tests_Model_OrderTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet( __DIR__ . '/OrderTest.xml' );
    }

    public function testAddEntry()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_sales_order' ), "Pre-Condition" );

        $data = [ 'cart_id' => 3, 'customer_id' => 2 ];
        FCom_Sales_Model_Order::i()->addNew( $data );

        $this->assertEquals( 3, $this->getConnection()->getRowCount( 'fcom_sales_order' ), "Insert failed" );
    }

    public function testSetPaid()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_sales_order' ), "Pre-Condition" );

        $order = FCom_Sales_Model_Order::i()->load( 2 );
        $order->paid();

        $this->assertEquals( 'paid', $order->status()->code );
    }

    public function testAddItems()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_sales_order' ), "Pre-Condition" );

        $order = FCom_Sales_Model_Order::i()->load( 2 );
        $this->assertEquals( 1, count( $order->items() ), "Before add failed" );

        $orderItem = [ 'order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10 ];
        FCom_Sales_Model_Order_Item::i()->addNew( $orderItem );

        $this->assertEquals( 2, count( $order->items() ), "After add failed" );
    }

    public function testItemsExist()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_sales_order' ), "Pre-Condition" );

        $order = FCom_Sales_Model_Order::i()->load( 2 );
        $this->assertEquals( 1, count( $order->items() ), "Before add failed" );

        $orderItem = [ 'order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10 ];
        FCom_Sales_Model_Order_Item::i()->addNew( $orderItem );

        $this->assertEquals( 2, count( $order->items() ), "After add failed" );

        $testItem = FCom_Sales_Model_Order_Item::i()->isItemExist( $order->id(), 1 );
        $this->assertTrue( is_object( $testItem ), "Item exists failed" );

        $testItem = FCom_Sales_Model_Order_Item::i()->isItemExist( $order->id(), 111111 );
        $this->assertFalse( is_object( $testItem ), "Item not exists failed" );
    }

    public function testAddPaymentMethod()
    {
        FCom_Sales_Main::i()->addPaymentMethod( 'paypal', 'FCom_PayPal_Frontend' );
        $methods = FCom_Sales_Main::i()->getPaymentMethods();
        $this->assertTrue( isset( $methods[ 'paypal' ] ) );
    }

    public function testAddShippingMethod()
    {
        FCom_Sales::i()->addShippingMethod( 'ups', 'FCom_ShippingUps_ShippingMethod' );
        $methods = FCom_Sales_Main::i()->getShippingMethods();
        $this->assertTrue( isset( $methods[ 'ups' ] ) );
    }
}
