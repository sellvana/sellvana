<?php

class FCom_Catalog_Tests_Model_ProductTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/product.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");


        $data = array('id' => 3, 'product_name' => 'Product 3', 'url_key' => 'product-3');
        //FCom_Catalog_Model_Product::create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_product'), "Inserting failed");
    }
}