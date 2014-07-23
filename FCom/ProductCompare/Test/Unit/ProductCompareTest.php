<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductCompare_Test_Unit_ProductCompareTest extends FCom_Test_DatabaseTestCase
{

    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/ProductCompareTest.xml');
    }

    public function testAddByAnonymous()
    {
        $mSet = FCom_ProductCompare_Model_Set::i();
        $sessionSet = $mSet->sessionSet(true);

        $this->assertNotEmpty($sessionSet, 'Not set session compare');

        $mSetItem = FCom_ProductCompare_Model_SetItem::i();
        $data = ['set_id' => $sessionSet->id, 'product_id' => 1];
        $mSetItem->create($data)->save();

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_compare_item'), "Inserting failed");

    }

    public function testAddByCustomer()
    {
        $mCustomer = FCom_Customer_Model_Customer::i();
        $data = ['id' => 2, 'email' => "test2@test.com", 'firstname' => "Test 2", 'password' => 123];
        $mCustomer->create($data)->save();
        $customer = $mCustomer->authenticate("test2@test.com", 123);

        $this->assertTrue($customer instanceof FCom_Customer_Model_Customer, 'Authenticate failed');

        $mSet = FCom_ProductCompare_Model_Set::i();
        $sessionSet = $mSet->sessionSet();

        $this->assertNotEmpty($sessionSet, 'Not set session compare');

        $mSetItem = FCom_ProductCompare_Model_SetItem::i();
        $data = ['set_id' => $sessionSet->id, 'product_id' => 2];

        $mSetItem->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_compare_item'), "Inserting failed");

    }
}
 