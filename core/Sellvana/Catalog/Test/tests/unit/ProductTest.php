<?php

class ProductTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/ProductTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_product');

        $data = ['id' => 3, 'product_name' => 'Product 3', 'url_key' => 'product-3'];
        Sellvana_Catalog_Model_Product::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_product');
    }

    public function testRemoveEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_product');

        $id = 2;
        $entry = Sellvana_Catalog_Model_Product::i()->load($id);
        $entry->delete();

        $this->tester->seeNumRecords(1, 'fcom_product');
    }

    public function testUpdateEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_product');

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
        $this->tester->seeNumRecords(2, 'fcom_product');

        $data = ['id' => 3, 'product_name' => 'Product 3'];
        Sellvana_Catalog_Model_Product::i()->create($data)->save();
        $this->tester->seeNumRecords(3, 'fcom_product');

        $entry = Sellvana_Catalog_Model_Product::i()->load(3);
        $this->assertNotEmpty($entry->url_key, "url_key generation failed");

        $this->assertEquals("product-3", $entry->url_key, "url_key algorithm changed");
    }

}
