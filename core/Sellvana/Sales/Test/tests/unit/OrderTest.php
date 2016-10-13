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

    public function testGetTextDescription()
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        $desc  = $order->getTextDescription();
        $this->tester->assertRegExp('/[A-z0-9]+x[0-9]+?\\n/', $desc, 'Text description is not correct.');
    }

    public function testOrderIsPayable()
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        $this->tester->assertFalse($order->isPayable(), 'Order payable checking failure.');

        $order->set('amount_due', 2)->save(false);
        $this->tester->assertTrue($order->isPayable(), 'Order payable checking failure.');
    }

    public function testGetPayableItems()
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        $items = $order->getPayableItems();
        $this->assertCount(2, $items, 'Payable items is not correct.');
    }

    public function testOrderIsShipable()
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        $this->tester->assertTrue($order->isShippable(), 'Order shippable checking failure.');
    }

    public function testGetShippableItems()
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        $items = $order->getShippableItems();
        $this->assertCount(1, $items, 'Shippable items is not correct.');
    }

    public function testGetLastCustomerComment()
    {
        $this->tester->seeNumRecords(0, 'fcom_sales_order_comment');
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        $this->tester->assertFalse($order->getLastCustomerComment(), 'Lastest comment is not correct');
        $this->tester->haveInDatabase('fcom_sales_order_comment', [
            'id'           => 1,
            'order_id'     => $order->id(),
            'comment_text' => 'Thankyou Sellvana.',
            'from_admin'   => 1,
            'create_at'    => BDb::i()->now()
        ]);

        $this->tester->seeNumRecords(1, 'fcom_sales_order_comment');
        $this->tester->assertFalse($order->getLastCustomerComment(), 'Lastest comment is not correct.');
        Sellvana_Sales_Model_Order_Comment::i()
            ->load(1)
            ->set('from_admin', 0)
            ->save(false);
        $this->tester->assertEquals(1, count($order->getLastCustomerComment()), 'Lastest comment is not correct.');
    }

    public function testOrderOverallStateProcess()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');

        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);

        $overall = $order->state()->overall();
        $this->assertInstanceOf(Sellvana_Sales_Model_Order_State_Overall::class, $overall, 'Can not get the overall state instance ');
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State::OVERALL, $overall->getType(), 'State type is not correct.');

        /** @var Sellvana_Sales_Model_Cart_State_Overall $state */
        $state = $overall->setPending();
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Overall::PENDING, $state->getValue(), 'Order overall state is incorrect');
        $this->tester->assertTrue($state->is(Sellvana_Sales_Model_Order_State_Overall::PENDING), 'Order state default is incorrect');

        $state = $overall->changeState(Sellvana_Sales_Model_Order_State_Overall::ARCHIVED);
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Overall::ARCHIVED, $state->getValue(), 'Order state default is incorrect');
        $this->tester->assertEquals('Archived', $state->getValueLabel(), 'State title is not correct');

        $this->tester->assertSame($order, $state->getModel(), 'Load model fail.');

        $this->tester->seeNumRecords(3, 'fcom_sales_order');
    }

    public function testPaymentStateProcess()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');

        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);

        $payment = $order->state()->payment();
        $this->assertInstanceOf(Sellvana_Sales_Model_Order_State_Payment::class, $payment, 'Can not get the overall state instance ');
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State::PAYMENT, $payment->getType(), 'State type is not correct.');

        /** @var Sellvana_Sales_Model_Cart_State_Payment $state */
        $state = $payment->setUnpaid();
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Payment::UNPAID, $state->getValue(), 'Order payment state is incorrect');
        $this->tester->assertTrue($state->is(Sellvana_Sales_Model_Order_State_Payment::UNPAID), 'Order state default is incorrect');
        $this->tester->assertFalse($payment->isComplete(), 'Payment state is not correct.');

        $state = $payment->changeState(Sellvana_Sales_Model_Order_State_Payment::PAID);
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Payment::PAID, $state->getValue(), 'Order state default is incorrect');
        $this->tester->assertTrue($payment->isComplete(), 'Payment state is not correct.');

        $state = $payment->setFree();
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Payment::FREE, $state->getState(), 'Order state default is incorrect');
        $this->tester->assertTrue($payment->isComplete(), 'Payment state is not correct.');
        $this->tester->assertEquals('Free', $state->getValueLabel(), 'State title is not correct');


        $this->tester->assertSame($order, $state->getModel(), 'Load model fail.');

        $this->tester->seeNumRecords(3, 'fcom_sales_order');
    }

    public function testReturnStateProcess()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');

        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(3);

        $return = $order->state()->returns();
        $this->assertInstanceOf(Sellvana_Sales_Model_Order_State_Return::class, $return, 'Can not get the overall state instance ');
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State::RETURNS, $return->getType(), 'State type is not correct.');

        /** @var Sellvana_Sales_Model_Cart_State_Payment $state */
        $state = $return->setProcessing();
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Return::PROCESSING, $state->getValue(), 'Order payment state is incorrect');
        $this->tester->assertTrue($state->is(Sellvana_Sales_Model_Order_State_Return::PROCESSING), 'Order state default is incorrect');

        $state = $return->changeState(Sellvana_Sales_Model_Order_State_Return::RETURNED);
        $this->tester->assertEquals(Sellvana_Sales_Model_Order_State_Return::RETURNED, $state->getValue(), 'Order state default is incorrect');
        $this->tester->assertEquals('Returned', $state->getValueLabel(), 'State title is not correct');

        $this->tester->assertSame($order, $state->getModel(), 'Load model fail.');

        $this->tester->seeNumRecords(3, 'fcom_sales_order');
    }

    public function testMarkAsPaid()
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        $order->markAsPaid();
        $items = $order->items();
        foreach ($items as $item) {
            $this->tester->assertTrue($item->getAmountCanPay() == 0 && $item->get('amount_paid'), 'Marking order item as paid fail.');
        }
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');

        $data = ['cart_id' => 3, 'customer_id' => 2, 'item_qty' => 5, 'subtotal' => 20, 'grand_total' => 30];
        Sellvana_Sales_Model_Order::i()->create($data)->save();

        $this->tester->seeNumRecords(4, 'fcom_sales_order');
    }

    public function testAddItems()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);
        $this->tester->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10, 'qty_ordered' => 20];
        Sellvana_Sales_Model_Order_Item::i()->create($orderItem)->save();

        $this->tester->assertEquals(2, count($order->items()), "After add failed");
    }

    public function testItemsExist()
    {
        $this->tester->seeNumRecords(3, 'fcom_sales_order');

        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);
        $this->tester->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10, 'qty_ordered' => 20];
        Sellvana_Sales_Model_Order_Item::i()->create($orderItem)->save();

        $this->tester->assertEquals(2, count($order->items()), "After add failed");

        $testItem = Sellvana_Sales_Model_Order_Item::i()->isItemExist($order->id(), 1);
        $this->assertObjectHasAttribute('id', $testItem, "Failed checking exisiting item.");

        $testItem = Sellvana_Sales_Model_Order_Item::i()->isItemExist($order->id(), 111111);
        $this->assertObjectNotHasAttribute('id', $testItem, "Failed checking exisiting item.");
    }

    public function testAddPaymentMethod()
    {
        Sellvana_Sales_Main::i()->addPaymentMethod('paypal', 'Sellvana_PaymentPaypal_PaymentMethod_ExpressCheckout');
        $methods = Sellvana_Sales_Main::i()->getPaymentMethods();
        $this->tester->assertTrue(isset($methods['paypal']));
    }

    public function testAddCheckoutMethod()
    {
        Sellvana_Sales_Main::i()->addCheckoutMethod('paypal', 'Sellvana_PaymentPaypal_Frontend_CheckoutMethod');
        $methods = Sellvana_Sales_Main::i()->getCheckoutMethods();
        $this->tester->assertTrue(isset($methods['paypal']));
    }

    public function testAddShippingMethod()
    {
        Sellvana_Sales_Main::i()->addCheckoutMethod('ups', 'Sellvana_ShippingUps_ShippingMethod');
        $methods = Sellvana_Sales_Main::i()->getShippingMethods();
        $this->tester->assertTrue(isset($methods['ups']));
    }
}
