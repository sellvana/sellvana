<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Customer_Tests_Model_CustomerTest
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */

class CustomerTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/CustomerTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer');

        $data = ['id' => 3, 'email' => "test3@test.com", 'firstname' => "Test 3"];
        Sellvana_Customer_Model_Customer::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_customer');
    }

    public function testDeleteEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer');

        $customer = Sellvana_Customer_Model_Customer::i()->load(2);
        $customer->delete();

        $this->tester->seeNumRecords(1, 'fcom_customer');
    }

    public function testSetPasswordHash()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer');

        $data = ['id' => 3, 'email' => "test3@test.com", 'firstname' => "Test 3", 'password' => 123];
        $customer = Sellvana_Customer_Model_Customer::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_customer');

        $this->assertTrue(!empty($customer->password_hash));
    }

    public function testAuthenticate()
    {
        $this->tester->seeNumRecords(2, 'fcom_customer');

        $data = ['id' => 3, 'email' => "test3@test.com", 'firstname' => "Test 3", 'password' => 123];
        Sellvana_Customer_Model_Customer::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_customer');

        $customer = Sellvana_Customer_Model_Customer::i()->authenticate("test3@test.com", 123);
        $this->assertTrue($customer instanceof Sellvana_Customer_Model_Customer);
    }

    public function testDefaultShippingAddress()
    {
        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer = Sellvana_Customer_Model_Customer::i()->load(1);
        $shippingAddress = $customer->getDefaultShippingAddress();
        $this->assertEquals($shippingAddress->firstname, $customer->firstname, "Shipping address not found");
    }

    public function testDefaultBillingAddress()
    {
        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer = Sellvana_Customer_Model_Customer::i()->load(2);
        $shippingAddress = $customer->getDefaultBillingAddress();
        $this->assertEquals($shippingAddress->firstname, $customer->firstname, "Billing address not found");
    }

    public function testRegister()
    {
        $this->markTestIncomplete(
          'This test require FCom_Frontend module which is not loaded in testing environment.'
        );

        $this->tester->seeNumRecords(2, 'fcom_customer');

        $data = ['email' => "test3@test.com", 'firstname' => "Test 3", 'password' => 123, 'password_confirm' => 123];
        Sellvana_Customer_Model_Customer::i()->register($data);

        $this->tester->seeNumRecords(3, 'fcom_customer');
    }
}
