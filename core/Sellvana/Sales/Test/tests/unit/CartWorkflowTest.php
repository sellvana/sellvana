<?php

/**
 * Class Sellvana_Sales_Test_Unit_CartWorkflowTest
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
class CartWorkflowTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Sellvana\Sales\UnitTester
     */
    protected $tester;

    /**
     * @var Sellvana_Customer_Model_Customer $customer
     */
    protected $customer;

    protected function _before()
    {
        $this->initDataSet();
        $this->customer = Sellvana_Customer_Model_Customer::i()->load(1)->login();
    }

    protected function _after()
    {
        if ($this->customer->isLoggedIn()) $this->customer->logout();
    }

    private function initDataSet()
    {
        $xml = simplexml_load_file(__DIR__ . '/CartWorkflowTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testCustomerCreatesNewCartAction()
    {
        Sellvana_Sales_Main::i()->workflowAction('customerCreatesNewCart');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->loadWhere([
            'customer_id' => $this->customer->id()
        ]);

        $this->assertInstanceOf(Sellvana_Sales_Model_Cart::class, $cart, 'Cart is not correct.');
        $this->tester->assertEquals(
            Sellvana_Sales_Model_Cart_State_Overall::ACTIVE,
            $cart->get('state_overall')
        );
    }

    public function testCustomerAddsItemsToCartAction()
    {
        Sellvana_Sales_Main::i()->workflowAction('customerCreatesNewCart');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->loadWhere([
            'customer_id' => $this->customer->id()
        ]);
        $result = [];
        $post = ["action" => "add", "qty" => 1, "id" => 1, 'cart' => $cart];
        Sellvana_Sales_Main::i()->workflowAction('customerAddsItemsToCart', ['post' => $post, 'result' => &$result]);
        $this->assertNotEmpty($result, 'Add items to cart fail.');
        $item = $result['items'][0];

        $this->tester->assertNotEmpty($item, 'Add items to cart fail.');
        $this->tester->assertTrue(isset($item['status']), 'Add items to cart fail.');
        $this->tester->assertEquals('added', $item['status'], 'Add items to cart fail.');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->loadWhere([
            'customer_id' => $this->customer->id()
        ]);
        $this->assertCount(1, $cart->items(), 'Add items to cart fail.');
    }
    
    public function testCustomerUpdatesCartAction()
    {
        $post = [];
        $result = [];
        Sellvana_Sales_Main::i()->workflowAction('customerCreatesNewCart');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->loadWhere([
            'customer_id' => $this->customer->id()
        ]);
        $cItem = $cart->addProduct(1);
        $post['qty'][$cItem->id()] = 3;
        Sellvana_Sales_Main::i()->workflowAction('customerUpdatesCart', [
            'post' => $post,
            'result' => &$result,
            'cart' => $cart
        ]);
        $item = $result['items'][0];

        $this->tester->assertNotEmpty($item, 'Update cart items fail.');
        $this->tester->assertTrue(isset($item['status']), 'Update cart items fail.');
        $this->tester->assertEquals('updated', $item['status'], 'Update cart items fail.');

        $post['qty'][$cItem->id()] = 0;
        $result = [];
        Sellvana_Sales_Main::i()->workflowAction('customerUpdatesCart', ['post' => $post, 'result' => &$result]);
        $item = $result['items'][0];

        $this->tester->assertNotEmpty($item, 'Update cart items fail.');
        $this->tester->assertTrue(isset($item['status']), 'Update cart items fail.');
        $this->tester->assertEquals('deleted', $item['status'], 'Update cart items fail.');
    }

    public function testCustomerRequestsShippingEstimate()
    {
        Sellvana_Sales_Main::i()->workflowAction('customerCreatesNewCart');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->loadWhere([
            'customer_id' => $this->customer->id()
        ]);
        $result = [];
        $post = [
            'shipping' => [
                'postcode' => 'qwerty123'
            ]
        ];
        Sellvana_Sales_Main::i()->workflowAction('customerRequestsShippingEstimate', [
            'post' => $post,
            'result' => &$result,
            'cart' => $cart
        ]);

        $this->tester->assertSame($post['shipping']['postcode'], $cart->get('shipping_postcode'), 'Request cart estimate fail.');
        $this->tester->assertTrue(isset($result['status']), 'Request cart estimate fail.');
        $this->tester->assertEquals('success', $result['status'], 'Request cart estimate fail.');
    }

    public function testCustomerAddsAndRemovesCouponCodeAction()
    {
        Sellvana_Sales_Main::i()->workflowAction('customerCreatesNewCart');
        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->loadWhere([
            'customer_id' => $this->customer->id()
        ]);
        $result = [];
        $post = [
            'coupon_code' => 'qwerty123'
        ];
        Sellvana_Sales_Main::i()->workflowAction('customerAddsCouponCode', [
            'post' => $post,
            'result' => &$result,
            'cart' => $cart
        ]);
        $this->tester->assertFalse(isset($result['error']), 'Can not add coupon code.');

        $this->assertInstanceOf(Sellvana_Sales_Model_Cart::class, $cart, 'Cart is not correct.');
        $this->tester->assertSame($post['coupon_code'], $cart->get('coupon_code'), 'Can not add coupon code');

        $result = [];
        $post = [
            'coupon_code' => 'qwerty123'
        ];

        Sellvana_Sales_Main::i()->workflowAction('customerAddsCouponCode', [
            'post' => $post,
            'result' => &$result,
            'cart' => $cart
        ]);
        $this->tester->assertTrue(isset($result['error']), 'Duplicate coupon code check fail.');

        $result = [];
        $post = [];

        Sellvana_Sales_Main::i()->workflowAction('customerAddsCouponCode', [
            'post' => $post,
            'result' => &$result,
            'cart' => $cart
        ]);
        $this->tester->assertTrue(isset($result['error']), 'Duplicate coupon code check fail.');

        $result = [];
        $post = [
            'coupon_code' => 'qwerty456'
        ];
        $this->tester->assertFalse(isset($result['error']), 'Can not add coupon code.');
        $this->assertCount(2, explode(',', $cart->get('coupon_code')), 'Can not add coupon code');

        $result = [];
        $post = [
            'coupon_code' => 'qwerty789'
        ];
        Sellvana_Sales_Main::i()->workflowAction('customerRemovesCouponCode', [
            'post' => $post,
            'result' => &$result,
        ]);
        $this->tester->assertTrue(isset($result['error']), 'Duplicate coupon code check fail.');

        $result = [];
        $post = [
            'coupon_code' => 'qwerty123'
        ];
        Sellvana_Sales_Main::i()->workflowAction('customerRemovesCouponCode', [
            'post' => $post,
            'result' => &$result,
        ]);
        $this->tester->assertNull($result['error'], 'Can not add coupon code.');
        $this->assertCount(1, explode(',', $cart->get('coupon_code')), 'Can not add coupon code');
    }

    public function testCustomerAbandonsCart()
    {
        $this->customer->login();
        Sellvana_Sales_Main::i()->workflowAction('customerAbandonsCart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->sessionCart();
        $this->assertEquals(Sellvana_Sales_Model_Cart_State_Overall::ABANDONED, $cart->state()->overall()->getValue(), 'Set cart state fail.');
    }
}