<?php

class FCom_Sales_Tests_Model_OrderTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/OrderTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_order'), "Pre-Condition");

        $data = array('cart_id' => 3, 'user_id' => 2);
        FCom_Sales_Model_Order::i()->add($data);

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_sales_order'), "Insert failed");
    }
}