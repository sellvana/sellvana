<?php

/**
 * Class Sellvana_Sales_Tests_Model_OrderTest
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 */

class OrderTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/OrderTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testOrderStateProcess()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');

        /** @var Sellvana_Sales_Model_Order $order1 */
        $order1 = Sellvana_Sales_Model_Order::i()->load(1);
        /** @var Sellvana_Sales_Model_Cart_State_Overall $state */
        $state = $order1->state()->overall()->setArchived();
        $this->tester->assertEquals(Sellvana_Sales_Model_Cart_State_Overall::ARCHIVED, $state->getValue(), 'Order overall state is incorrect');
        $this->tester->assertEquals(Sellvana_Sales_Model_Cart_State_Overall::ARCHIVED, $state->setDefaultState()->getValue(), 'Order state default is incorrect');

        $this->tester->assertSame($order1, $state->getModel(), 'Load model fail.');

        /** @var Sellvana_Sales_Model_Order $order2 */
        $order2 = Sellvana_Sales_Model_Order::i()->load(2);
        /** @var Sellvana_Sales_Model_Cart_State_Payment $state */
        $state = $order2->state()->payment()->setFree();
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Payment::FREE, $state->getValue(), 'Order payment state is incorrect');
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Payment::FREE, $state->setDefaultState()->getValue(), 'Order state default is incorrect');

        $this->tester->assertSame($order2, $state->getModel(), 'Load model fail.');

        /** @var Sellvana_Sales_Model_Order $order3 */
        $order3 = Sellvana_Sales_Model_Order::i()->load(3);
        /** @var Sellvana_Sales_Model_Cart_State_Payment $state */
        $state = $order3->state()->returns()->setProcessing();
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Return::PROCESSING, $state->getValue(), 'Order payment state is incorrect');
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Return::PROCESSING, $state->setDefaultState()->getValue(), 'Order state default is incorrect');

        $this->tester->assertSame($order3, $state->getModel(), 'Load model fail.');

        $this->tester->seeNumRecords(3, 'fcom_sales_order');
    }

    public function testGetCart()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);
        $this->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty_ordered' => 1];
        Sellvana_Sales_Model_Order_Item::i()->create($orderItem)->save();

        $this->assertEquals(1, count($order->items()), "After add failed");

        /** @var Sellvana_Sales_Model_Order_Item $testItem */
        $testItem = Sellvana_Sales_Model_Order_Item::i();
        $testItem->isItemExist($order->id(), 1);
        $this->assertTrue(is_object($testItem), "Item exists failed");

        $testItem = Sellvana_Sales_Model_Order_Item::i();
        $testItem->isItemExist($order->id(), 111111);
        $this->assertFalse(is_object($testItem), "Item not exists failed");
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_order');

        $data = ['cart_id' => 3, 'customer_id' => 2, 'item_qty' => 5, 'subtotal' => 20];
        Sellvana_Sales_Model_Order::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_sales_order');
    }

    public function testAddItems()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_order');
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);
        $this->tester->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10];
        Sellvana_Sales_Model_Order_Item::i()->create($orderItem)->save();

        $this->tester->assertEquals(2, count($order->items()), "After add failed");
    }

    public function testItemsExist()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_order');

        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);
        $this->tester->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10];
        Sellvana_Sales_Model_Order_Item::i()->create($orderItem)->save();

        $this->tester->assertEquals(2, count($order->items()), "After add failed");

        $testItem = Sellvana_Sales_Model_Order_Item::i()->isItemExist($order->id(), 1);
        $this->tester->assertTrue(is_object($testItem), "Item exists failed");

        $testItem = Sellvana_Sales_Model_Order_Item::i()->isItemExist($order->id(), 111111);
        $this->tester->assertFalse(is_object($testItem), "Item not exists failed");
    }

    public function testAddPaymentMethod()
    {
        Sellvana_Sales_Main::i()->addPaymentMethod('paypal', 'Sellvana_PaymentPaypal_Frontend');
        $methods = Sellvana_Sales_Main::i()->getPaymentMethods();
        $this->tester->assertTrue(isset($methods['paypal']));
    }
}
