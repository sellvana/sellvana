<?php

/**
 * Class Sellvana_Sales_Test_Unit_OrderWorkflowTest
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
class OrderWorkflowTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Sales\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->initDataSet();
    }

    private function initDataSet()
    {
        $xml = simplexml_load_file(__DIR__ . '/OrderWorkflowTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testCustomerPlacesOrder()
    {
        Sellvana_Customer_Model_Customer::i()->load(1)->login();
        Sellvana_Sales_Main::i()->workflowAction('customerCreatesNewCart');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->sessionCart();
        $result = [];
        Sellvana_Sales_Main::i()->workflowAction('customerPlacesOrder', [
            'result' => &$result,
            'cart' => $cart
        ]);

        $this->tester->assertTrue(isset($result['success']), 'Placing order is not completed.');

        /** @var Sellvana_Sales_Model_Order $order */
        $order = $result['order'];
        $this->assertInstanceOf(Sellvana_Sales_Model_Order::class, $order, 'Order is not correct.');
        $this->tester->assertEquals('placed', $order->get('state_overall'), 'Order state is not correct.');
        $this->tester->assertTrue($order->get('shipping_method') == $cart->get('shipping_method'), 'Import data from cart fail.');
    }

    public function testCustomerCreatesAccountFromOrder()
    {
        // Make sure no any customer is logged in
        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer = Sellvana_Customer_Model_Customer::i()->sessionUser();
        if ($customer && $customer->isLoggedIn()) $customer->logout();

        $result = [];
        $post = [
            'password' => '12341qaz',
            'password_confirm' => '12341qaz',
        ];
        Sellvana_Sales_Main::i()->workflowAction('customerCreatesAccountFromOrder', [
            'post' => $post,
            'order_id' => 4,
            'result' => &$result
        ]);

        $this->tester->assertNotEmpty($result, 'Create account fail.');
        $this->tester->assertTrue(isset($result['customer']), 'Can not create customer.');
        $this->assertInstanceOf(Sellvana_Customer_Model_Customer::class, $result['customer'], 'Customer is not correct.');
        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer = $result['customer'];
        $this->tester->assertEquals($customer->id(), Sellvana_Customer_Model_Customer::i()->sessionUserId(), 'Customer is not correct.');

        $addresses = $customer->getAddresses(true);
        $this->assertCount(1, $addresses, 'Customer addresses is not correct.');
        /** @var Sellvana_Customer_Model_Address $address */
        $address = $addresses[0];
        $this->tester->assertSame($customer->getDefaultShippingAddress()->as_array(), $address->as_array(), 'Default shipping data is not correct.');
    }

    public function address()
    {
        return [
            [
                [
                    'shipping_company' => 'Ceres',
                    'shipping_attn' => 'abcdef',
                    'shipping_firstname' => 'Test',
                    'shipping_lastname' => 'Ceres',
                    'shipping_street1' => '123 Le Binh',
                    'shipping_street2' => '',
                    'shipping_city' => 'HCM',
                    'shipping_postcode' => '70000',
                    'shipping_country' => 'UK',
                    'shipping_phone' => '0909000000',
                    'shipping_fax' => '',
                ],
                [
                    'billing_company' => 'Ceres',
                    'billing_attn' => 'abcdef',
                    'billing_firstname' => 'Test',
                    'billing_lastname' => 'Ceres',
                    'billing_street1' => '123 Le Binh',
                    'billing_street2' => '',
                    'billing_city' => 'HCM',
                    'billing_postcode' => '70000',
                    'billing_country' => 'UK',
                    'billing_phone' => '0909000000',
                    'billing_fax' => '',
                ]
            ]
        ];
    }

    /**
     * @dataProvider address
     * @throws BException
     */
    public function testAdminUpdatesOrderShippingAndBillingAddress($shippingAddr, $billingAddr)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = Sellvana_Sales_Model_Order::i()->load(1);
        Sellvana_Sales_Main::i()->workflowAction('adminUpdatesOrderShippingAddress', [
            'address' => new BData($shippingAddr),
            'order' => $order
        ]);
        $this->tester->assertSame($shippingAddr, $order->addressAsArray('shipping'), 'Update order shipping address fail.');

        Sellvana_Sales_Main::i()->workflowAction('adminUpdatesOrderBillingAddress', [
            'address' => new BData($billingAddr),
            'order' => $order
        ]);
        $this->tester->assertSame($billingAddr, $order->addressAsArray('billing'), 'Update order billing address fail.');
    }
}