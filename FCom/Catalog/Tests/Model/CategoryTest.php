<?php

class FCom_Catalog_Tests_Model_CategoryTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/CategoryTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");

        $category = FCom_Catalog_Model_Category::load(1);
        $category->createChild("Category 2");

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_category'), "Insert failed");
    }

    public function testAddChildOfChildEntry()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");

        $category = FCom_Catalog_Model_Category::load(1);
        $child = $category->createChild("Category 2");

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_category'), "Insert failed");

        $child->createChild("Category 3");
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_category'), "Insert first child failed");
    }

    public function testFindProductsByCategory()
    {
        $categoryId = 1;
        $category = FCom_Catalog_Model_Category::load($categoryId);

        $this->assertTrue(is_object($category));

        $products = $category->products();

        $this->assertEquals(1, count($products));
    }

    public function testUrlKey()
    {
        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");

        $category = FCom_Catalog_Model_Category::load(1);
        $categoryChild = $category->createChild("Category 2");

        $this->assertTrue(!empty($categoryChild->url_key), "Not set url_key");
    }
}