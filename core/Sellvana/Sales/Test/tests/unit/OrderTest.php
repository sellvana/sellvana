<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        $xml = simplexml_load_file(__DIR__ . '/OrderTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_order');

        $data = ['cart_id' => 3, 'customer_id' => 2];
        Sellvana_Sales_Model_Order::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_sales_order');
    }

    public function testAddItems()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_order');
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);
        $this->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10];
        Sellvana_Sales_Model_Order_Item::i()->create($orderItem)->save();

        $this->assertEquals(2, count($order->items()), "After add failed");
    }

    public function testItemsExist()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_order');

        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(2);
        $this->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10];
        Sellvana_Sales_Model_Order_Item::i()->create($orderItem)->save();

        $this->assertEquals(2, count($order->items()), "After add failed");

        $testItem = Sellvana_Sales_Model_Order_Item::i()->isItemExist($order->id(), 1);
        $this->assertTrue(is_object($testItem), "Item exists failed");

        $testItem = Sellvana_Sales_Model_Order_Item::i()->isItemExist($order->id(), 111111);
        $this->assertFalse(is_object($testItem), "Item not exists failed");
    }

    public function testAddPaymentMethod()
    {
        Sellvana_Sales_Main::i()->addPaymentMethod('paypal', 'Sellvana_PaymentPaypal_Frontend');
        $methods = Sellvana_Sales_Main::i()->getPaymentMethods();
        $this->assertTrue(isset($methods['paypal']));
    }
}
