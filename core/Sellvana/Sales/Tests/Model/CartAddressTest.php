<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Tests_Model_CartAddressTest
 *
 * @property Sellvana_Sales_Model_Cart_Address $Sellvana_Sales_Model_Cart_Address
 */

class Sellvana_Sales_Tests_Model_CartAddressTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/AddressTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_checkout_address'), "Pre-Condition");

        $cartId = 1;

        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $this->Sellvana_Sales_Model_Cart_Address->newAddress($cartId, 'billing', $data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_checkout_address'), "Insert failed");
    }

    public function testAddEntryExists()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_checkout_address'), "Pre-Condition");

        $cartId = 1;

        $data = ['city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $this->Sellvana_Sales_Model_Cart_Address->newAddress($cartId, 'shipping', $data);

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_checkout_address'), "Update failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_checkout_address'), "Pre-Condition");

        $cartId = 1;

        $data = ['city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $address = $this->Sellvana_Sales_Model_Cart_Address->findByCartType($cartId, 'shipping');
        $this->assertEquals("Los Angeles", $address->city, "Address not found");

        $this->Sellvana_Sales_Model_Cart_Address->newAddress($cartId, 'shipping', $data);

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_checkout_address'), "Update failed");

        $address = $this->Sellvana_Sales_Model_Cart_Address->findByCartType($cartId, 'shipping');
        $this->assertEquals($data['city'], $address->city, "Address not found");
    }

    public function testAddEntryNewCart()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_checkout_address'), "Pre-Condition");

        $cartId = 3;

        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $this->Sellvana_Sales_Model_Cart_Address->newAddress($cartId, 'billing', $data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_checkout_address'), "Insert failed");
    }

    public function testAddressBelongToCart()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_checkout_address'), "Pre-Condition");

        $cartId = 3;
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave'];

        $this->Sellvana_Sales_Model_Cart_Address->newAddress($cartId, 'billing', $data);
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_checkout_address'), "Insert failed");

        $address = $this->Sellvana_Sales_Model_Cart_Address->findByCartType($cartId, 'billing');
        $this->assertEquals($cartId, $address->cart_id, "Address do not belong to cart");
    }
}
