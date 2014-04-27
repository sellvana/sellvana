<?php

class FCom_Sales_Tests_Model_CartAddressTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet( __DIR__ . '/AddressTest.xml' );
    }

    public function testAddEntry()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Pre-Condition" );

        $cartId = 1;

        $data = [ 'id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave' ];

        FCom_Sales_Model_Cart_Address::i()->newAddress( $cartId, 'billing', $data );

        $this->assertEquals( 3, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Insert failed" );
    }

    public function testAddEntryExists()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Pre-Condition" );

        $cartId = 1;

        $data = [ 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave' ];

        FCom_Sales_Model_Cart_Address::i()->newAddress( $cartId, 'shipping', $data );

        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Update failed" );
    }

    public function testUpdateEntry()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Pre-Condition" );

        $cartId = 1;

        $data = [ 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave' ];

        $address = FCom_Sales_Model_Cart_Address::i()->findByCartType( $cartId, 'shipping' );
        $this->assertEquals( "Los Angeles", $address->city, "Address not found" );

        FCom_Sales_Model_Cart_Address::i()->newAddress( $cartId, 'shipping', $data );

        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Update failed" );

        $address = FCom_Sales_Model_Cart_Address::i()->findByCartType( $cartId, 'shipping' );
        $this->assertEquals( $data[ 'city' ], $address->city, "Address not found" );
    }

    public function testAddEntryNewCart()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Pre-Condition" );

        $cartId = 3;

        $data = [ 'id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave' ];

        FCom_Sales_Model_Cart_Address::i()->newAddress( $cartId, 'billing', $data );

        $this->assertEquals( 3, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Insert failed" );
    }

    public function testAddressBelongToCart()
    {
        $this->assertEquals( 2, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Pre-Condition" );

        $cartId = 3;
        $data = [ 'id' => 3, 'city' => "Big city", 'country' => 'US',
            'region' => 'California', 'firstname' => "Test 1", 'street1' => '5th Ave' ];

        FCom_Sales_Model_Cart_Address::i()->newAddress( $cartId, 'billing', $data );
        $this->assertEquals( 3, $this->getConnection()->getRowCount( 'fcom_checkout_address' ), "Insert failed" );

        $address = FCom_Sales_Model_Cart_Address::i()->findByCartType( $cartId, 'billing' );
        $this->assertEquals( $cartId, $address->cart_id, "Address do not belong to cart" );
    }
}