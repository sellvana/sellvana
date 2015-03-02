<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Catalog_Test_Unit_ProductTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/ProductTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");

        $data = ['id' => 3, 'product_name' => 'Product 3', 'url_key' => 'product-3'];
        Sellvana_Catalog_Model_Product::i()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_product'), "Inserting failed");
    }

    public function testRemoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");

        $id = 2;
        $entry = Sellvana_Catalog_Model_Product::i()->load($id);
        $entry->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_product'), "Deleting failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");

        $id = 2;
        $entry = Sellvana_Catalog_Model_Product::i()->load($id);
        $this->assertEquals("Product 2", $entry->product_name, "Pre-Condition");

        $entry->product_name = "Product two";
        $entry->save();

        $entry = Sellvana_Catalog_Model_Product::i()->load($id);
        $this->assertEquals("Product two", $entry->product_name, "Update failed");
    }

    public function testGeneratingUrlKey()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");

        $data = ['id' => 3, 'product_name' => 'Product 3'];
        Sellvana_Catalog_Model_Product::i()->create($data)->save();
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_product'), "Inserting failed");

        $entry = Sellvana_Catalog_Model_Product::i()->load(3);
        $this->assertNotEmpty($entry->url_key, "url_key generation failed");

        $this->assertEquals("product-3", $entry->url_key, "url_key algorithm changed");
    }

}
