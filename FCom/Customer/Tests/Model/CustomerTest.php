<?php

class FCom_Customer_Tests_Model_CustomerTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/CustomerTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Pre-Condition");

        $data = array('id' => 3, 'email' => "test3@test.com", 'firstname' => "Test 3");
        FCom_Customer_Model_Customer::create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_customer'), "Insert failed");
    }

    public function testDeleteEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Pre-Condition");

        $customer = FCom_Customer_Model_Customer::load(2);
        $customer->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_customer'), "Delete failed");
    }
}