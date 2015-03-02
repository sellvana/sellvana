<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Tests_Model_CategoryTest
 *
 * @property Sellvana_Catalog_Model_Category $Sellvana_Catalog_Model_Category
 */

class Sellvana_Catalog_Tests_Model_CategoryTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/CategoryTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");

        $category = $this->Sellvana_Catalog_Model_Category->load(1);
        $category->createChild("Category 3");

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_category'), "Insert failed");
    }

    public function testAddChildOfChildEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");

        $category = $this->Sellvana_Catalog_Model_Category->load(1);
        $child = $category->createChild("Category 3");

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_category'), "Insert failed");

        $child->createChild("Category 4");
        $this->assertEquals(4, $this->getConnection()->getRowCount('fcom_category'), "Insert first child failed");
    }

    public function testFindProductsByCategory()
    {
        $categoryId = 1;
        $category = $this->Sellvana_Catalog_Model_Category->load($categoryId);

        $this->assertTrue(is_object($category));

        $products = $category->products();

        $this->assertEquals(1, count($products));
    }

    public function testUrlKey()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");

        $category = $this->Sellvana_Catalog_Model_Category->load(1);
        $categoryChild = $category->createChild("Category 3");

        $this->assertTrue(!empty($categoryChild->url_key), "Not set url_key");
    }

    public function testRenameEntry()
    {
        $category = $this->Sellvana_Catalog_Model_Category->load(2);
        $category->rename("CategoryNew 2");

        $category = $this->Sellvana_Catalog_Model_Category->load(2);
        $this->assertEquals("CategoryNew 2", $category->node_name, "Rename failed");
    }

    public function testMoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");

        $category = $this->Sellvana_Catalog_Model_Category->load(1);
        $child = $category->createChild("Category 3");

        $this->assertEquals(1, $child->parent_id);

        $child->move(2);
        $this->assertEquals(2, $child->parent_id, "Move failed");
    }

    public function testGetParent()
    {
        $category = $this->Sellvana_Catalog_Model_Category->load(2);
        $parent = $category->parent();
        $this->assertEquals(1, $parent->id(), "Parent not found");
    }

    public function testGetChildren()
    {
        $category = $this->Sellvana_Catalog_Model_Category->load(1);
        $children = $category->children();
        $this->assertEquals(1, count($children), "Children not found");
    }
}
