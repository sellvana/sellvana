<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_CartAddressTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/CartAddressTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Pre-Condition");

        $cartId = 1;
        $mCartAddress = FCom_Sales_Model_Cart_Address::i(true);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $mCartAddress->newAddress($cartId, 'billing', $data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Insert failed");
    }

    public function testAddEntryExists()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Pre-Condition");

        $cartId = 1;
        $mCartAddress = FCom_Sales_Model_Cart_Address::i(true);
        $data = ['city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $mCartAddress->newAddress($cartId, 'shipping', $data);

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Update failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Pre-Condition");

        $cartId = 1;
        $mCartAddress = FCom_Sales_Model_Cart_Address::i(true);
        $data = ['city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $address = $mCartAddress->findByCartType($cartId, 'shipping');
        $this->assertEquals("Los Angeles", $address->city, "Address not found");

        $mCartAddress->newAddress($cartId, 'shipping', $data);

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Update failed");

        $address = $mCartAddress->findByCartType($cartId, 'shipping');
        $this->assertEquals($data['city'], $address->city, "Address not found");
    }

    public function testAddEntryNewCart()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Pre-Condition");

        $cartId = 3;
        $mCartAddress = FCom_Sales_Model_Cart_Address::i(true);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $mCartAddress->newAddress($cartId, 'billing', $data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Insert failed");
    }

    public function testAddressBelongToCart()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Pre-Condition");
        $mCartAddress = FCom_Sales_Model_Cart_Address::i(true);
        $cartId = 3;
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $mCartAddress->newAddress($cartId, 'billing', $data);
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_cart_address'), "Insert failed");

        $address = $mCartAddress->findByCartType($cartId, 'billing');
        $this->assertEquals($cartId, $address->cart_id, "Address do not belong to cart");
    }
}
