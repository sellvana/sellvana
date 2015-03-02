<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Tests_Model_OrderTest
 *
 * @property Sellvana_Sales $Sellvana_Sales
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Item $Sellvana_Sales_Model_Order_Item
 */

class Sellvana_Sales_Tests_Model_OrderTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/OrderTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $data = ['cart_id' => 3, 'customer_id' => 2];
        $this->Sellvana_Sales_Model_Order->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_order'), "Insert failed");
    }

    public function testSetPaid()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $order = $this->Sellvana_Sales_Model_Order->load(2);
        $order->paid();

        $this->assertEquals('paid', $order->status()->code);
    }

    public function testAddItems()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $order = $this->Sellvana_Sales_Model_Order->load(2);
        $this->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10];
        $this->Sellvana_Sales_Model_Order_Item->create($orderItem)->save();

        $this->assertEquals(2, count($order->items()), "After add failed");
    }

    public function testItemsExist()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $order = $this->Sellvana_Sales_Model_Order->load(2);
        $this->assertEquals(1, count($order->items()), "Before add failed");

        $orderItem = ['order_id' => $order->id(), 'product_id' => 1, 'qty' => 1, 'total' => 10];
        $this->Sellvana_Sales_Model_Order_Item->create($orderItem)->save();

        $this->assertEquals(2, count($order->items()), "After add failed");

        $testItem = $this->Sellvana_Sales_Model_Order_Item->isItemExist($order->id(), 1);
        $this->assertTrue(is_object($testItem), "Item exists failed");

        $testItem = $this->Sellvana_Sales_Model_Order_Item->isItemExist($order->id(), 111111);
        $this->assertFalse(is_object($testItem), "Item not exists failed");
    }

    public function testAddPaymentMethod()
    {
        $this->Sellvana_Sales_Main->addPaymentMethod('paypal', 'Sellvana_PaymentPaypal_Frontend');
        $methods = $this->Sellvana_Sales_Main->getPaymentMethods();
        $this->assertTrue(isset($methods['paypal']));
    }

    public function testAddShippingMethod()
    {
        $this->Sellvana_Sales->addShippingMethod('ups', 'Sellvana_ShippingUps_ShippingMethod');
        $methods = $this->Sellvana_Sales_Main->getShippingMethods();
        $this->assertTrue(isset($methods['ups']));
    }
}
