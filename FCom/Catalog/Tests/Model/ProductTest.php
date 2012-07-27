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
        FCom_Catalog_Model_Product::create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_product'), "Inserting failed");
    }

    public function testRemoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");

        $id = 2;
        $entry = FCom_Catalog_Model_Product::load($id);
        $entry->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_product'), "Deleting failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");

        $id = 2;
        $entry = FCom_Catalog_Model_Product::load($id);
        $this->assertEquals("Product 2", $entry->product_name, "Pre-Condition");

        $entry->product_name = "Product two";
        $entry->save();

        $entry = FCom_Catalog_Model_Product::load($id);
        $this->assertEquals("Product two", $entry->product_name, "Update failed");
    }

    public function testGeneratingUrlKey()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");

        $data = array('id' => 3, 'product_name' => 'Product 3');
        FCom_Catalog_Model_Product::create($data)->save();
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_product'), "Inserting failed");

        $entry = FCom_Catalog_Model_Product::load(3);
        $this->assertTrue(!empty($entry->url_key), "url_key generation failed");
    }

}