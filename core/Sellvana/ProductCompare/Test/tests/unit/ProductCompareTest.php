<?php defined('BUCKYBALL_ROOT_DIR') || die();

class ProductCompareTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/ProductCompareTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddByAnonymous()
    {
        /** @var Sellvana_ProductCompare_Model_Set $mSet */
        $mSet = Sellvana_ProductCompare_Model_Set::i();
        $sessionSet = $mSet->sessionSet(true);

        $this->assertNotEmpty($sessionSet, 'Not set session compare');

        $mSetItem = Sellvana_ProductCompare_Model_SetItem::i();
        $data = ['set_id' => $sessionSet->id, 'product_id' => 1];
        $mSetItem->create($data)->save();

        $this->tester->seeNumRecords(2, 'fcom_compare_item');
    }

    public function testAddByCustomer()
    {
        /** @var Sellvana_Customer_Model_Customer $mCustomer */
        $mCustomer = Sellvana_Customer_Model_Customer::i();
        $data = ['id' => 2, 'email' => "test2@test.com", 'firstname' => "Test 2", 'password' => 123];
        $mCustomer->create($data)->save();
        $customer = $mCustomer->authenticate("test2@test.com", 123);

        $this->assertTrue($customer instanceof Sellvana_Customer_Model_Customer, 'Authenticate failed');

        /** @var Sellvana_ProductCompare_Model_Set $mSet */
        $mSet = Sellvana_ProductCompare_Model_Set::i();
        $sessionSet = $mSet->sessionSet();

        $this->assertNotEmpty($sessionSet, 'Not set session compare');

        $mSetItem = Sellvana_ProductCompare_Model_SetItem::i();
        $data = ['set_id' => $sessionSet->id, 'product_id' => 2];

        $mSetItem->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_compare_item');
    }
}
 