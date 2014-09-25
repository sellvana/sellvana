<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_Tests_Model_AddressTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/AddressTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer_address'), "Pre-Condition");

        $cust = $this->FCom_Customer_Model_Customer->load(1);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US', 'region' => 'California', 'firstname' => "Test 1"];
        $this->FCom_Customer_Model_Address->import($data, $cust, 'billing');

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer_address'), "Insert failed");
    }

    public function testAddressBelongToCustomer()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer_address'), "Pre-Condition");

        $cust = $this->FCom_Customer_Model_Customer->load(1);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US', 'region' => 'California', 'firstname' => "Test 1"];
        $address = $this->FCom_Customer_Model_Address->import($data, $cust, 'billing');

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer_address'), "Insert failed");
        $this->assertEquals($cust->id(), $address->customer_id, "Address association to customer failed");
    }

    public function testAddressSetAsgetDefaultBillingAddress()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer_address'), "Pre-Condition");

        $cust = $this->FCom_Customer_Model_Customer->load(1);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US', 'region' => 'California', 'firstname' => "Test 1"];
        $address = $this->FCom_Customer_Model_Address->import($data, $cust, 'billing');

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer_address'), "Insert failed");

        $defaultBilling = $cust->getDefaultBillingAddress();
        $this->assertEquals($address->id(), $defaultBilling->id(), "Billing address association with customer failed");
    }

    public function testDeleteEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer_address'), "Pre-Condition");

        $address = $this->FCom_Customer_Model_Address->load(1);
        $address->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_customer_address'), "Address delete failed");
    }

    public function testClearCustomerDefaultBillingShippingID()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer_address'), "Pre-Condition");

        $customer = $this->FCom_Customer_Model_Customer->load(1);
        $address = $customer->defaultShipping();
        $address->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_customer_address'), "Address delete failed");

        //refresh customer cache
        $customer = $this->FCom_Customer_Model_Customer->load(1);


        $this->assertTrue($customer->defaultShipping() == false);

        $this->assertTrue($customer->default_shipping_id == null);
    }
}
