<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_Test_Unit_CustomerTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/CustomerTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Pre-Condition");

        $data = ['id' => 3, 'email' => "test3@test.com", 'firstname' => "Test 3"];
        $mCustomer = FCom_Customer_Model_Customer::i();
        $mCustomer->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer'), "Insert failed");
    }

    public function testDeleteEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Pre-Condition");
        $mCustomer = FCom_Customer_Model_Customer::i();
        $customer = $mCustomer->load(2);
        $customer->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_customer'), "Delete failed");
    }

    public function testSetPasswordHash()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Pre-Condition");
        $mCustomer = FCom_Customer_Model_Customer::i();
        $data = ['id' => 3, 'email' => "test3@test.com", 'firstname' => "Test 3", 'password' => 123];
        $customer = $mCustomer->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer'), "Insert failed");

        $this->assertTrue(!empty($customer->password_hash));
    }

    public function testAuthenticate()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Pre-Condition");
        $mCustomer = FCom_Customer_Model_Customer::i();
        $data = ['id' => 3, 'email' => "test3@test.com", 'firstname' => "Test 3", 'password' => 123];
        $mCustomer->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer'), "Insert failed");

        $customer = $mCustomer->authenticate("test3@test.com", 123);
        $this->assertTrue($customer instanceof FCom_Customer_Model_Customer);
    }

    public function testDefaultShippingAddress()
    {
        $mCustomer = FCom_Customer_Model_Customer::i();
        $customer = $mCustomer->load(1);
        $shippingAddress = $customer->defaultShipping();
        $this->assertEquals($shippingAddress->firstname, $customer->firstname, "Shipping address not found");
    }

    public function testDefaultBillingAddress()
    {
        $mCustomer = FCom_Customer_Model_Customer::i();
        $customer =  $mCustomer->load(2);
        $shippingAddress = $customer->defaultBilling();
        $this->assertEquals($shippingAddress->firstname, $customer->firstname, "Billing address not found");
    }

    public function testRegister()
    {
        $this->markTestIncomplete(
          'This test require FCom_Frontend module which is not loaded in testing environment.'
        );
        $mCustomer = FCom_Customer_Model_Customer::i();
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Pre-Condition");

        $data = ['email' => "test3@test.com", 'firstname' => "Test 3", 'password' => 123, 'password_confirm' => 123];
        $mCustomer->register($data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer'), "Insert failed");
    }
}
