<?php

class FCom_Sales_Tests_Model_OrderTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/OrderTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $data = array('cart_id' => 3, 'user_id' => 2);
        FCom_Sales_Model_Order::i()->add($data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_order'), "Insert failed");
    }

    public function testSetPaid()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $order = FCom_Sales_Model_Order::load(2);
        $order->paid();

        $this->assertEquals('paid', $order->status()->code);
    }

    public function testAddItems()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $order = FCom_Sales_Model_Order::load(2);
        $this->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = array('order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10);
        FCom_Sales_Model_OrderItem::i()->add($orderItem);

        $this->assertEquals(2, count($order->items()), "After add failed");
    }

    public function testItemsExist()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $order = FCom_Sales_Model_Order::load(2);
        $this->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = array('order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10);
        FCom_Sales_Model_OrderItem::i()->add($orderItem);

        $this->assertEquals(2, count($order->items()), "After add failed");

        $testItem = FCom_Sales_Model_OrderItem::i()->isItemExist($order->id(), 1);
        $this->assertTrue(is_object($testItem), "Item exists failed");

        $testItem = FCom_Sales_Model_OrderItem::i()->isItemExist($order->id(), 111111);
        $this->assertFalse(is_object($testItem), "Item not exists failed");
    }
}