<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Tests_Model_AddressTest
 *
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */

class AddressTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/AddressTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer_address');

        $cust = Sellvana_Customer_Model_Customer::i()->load(1);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US', 'region' => 'California', 'firstname' => "Test 1"];
        Sellvana_Customer_Model_Address::i()->import($data, $cust, 'billing');

        $this->tester->seeNumRecords(3, 'fcom_customer_address');
    }

    public function testAddressBelongToCustomer()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer_address');

        $cust = Sellvana_Customer_Model_Customer::i()->load(1);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US', 'region' => 'California', 'firstname' => "Test 1"];
        $address = Sellvana_Customer_Model_Address::i()->import($data, $cust, 'billing');

        $this->tester->seeNumRecords(3, 'fcom_customer_address');
        $this->assertEquals($cust->id(), $address->customer_id, "Address association to customer failed");
    }

    public function testAddressSetAsgetDefaultBillingAddress()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer_address');

        /** @var Sellvana_Customer_Model_Customer $cust */
        $cust = Sellvana_Customer_Model_Customer::i()->load(1);
        $data = ['id' => 3, 'city' => "Big city", 'country' => 'US', 'region' => 'California', 'firstname' => "Test 1"];
        /** @var Sellvana_Customer_Model_Address $address */
        $address = Sellvana_Customer_Model_Address::i()->import($data, $cust, 'billing');

        $this->tester->seeNumRecords(3, 'fcom_customer_address');

        $defaultBilling = $cust->getDefaultBillingAddress();
        $this->assertEquals($address->id(), $defaultBilling->id(), "Billing address association with customer failed");
    }

    public function testDeleteEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer_address');

        $address = Sellvana_Customer_Model_Address::i()->load(1);
        $address->delete();

        $this->tester->seeNumRecords(1, 'fcom_customer_address');
    }

    public function testClearCustomerDefaultBillingShippingID()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer_address');

        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer = Sellvana_Customer_Model_Customer::i()->load(1);
        $address = $customer->getDefaultShippingAddress();
        $address->delete();

        $this->tester->seeNumRecords(1, 'fcom_customer_address');

        //refresh customer cache
        $customer = Sellvana_Customer_Model_Customer::i()->load(1);

        $this->assertTrue($customer->getDefaultShippingAddress() == false);

        $this->assertTrue($customer->default_shipping_id == null);
    }
}
