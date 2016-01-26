<?php defined('BUCKYBALL_ROOT_DIR') || die();

class CategoryTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/CategoryTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_category');

        $category = Sellvana_Catalog_Model_Category::i()->load(1);
        $category->createChild("Category 3");

        $this->tester->seeNumRecords(3, 'fcom_category');
    }

    public function testAddChildOfChildEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_category');

        $category = Sellvana_Catalog_Model_Category::i()->load(1);
        $child = $category->createChild("Category 3");

        $this->tester->seeNumRecords(3, 'fcom_category');

        $child->createChild("Category 4");
        $this->tester->seeNumRecords(4, 'fcom_category');
    }

    public function testFindProductsByCategory()
    {
        $categoryId = 1;
        $category = Sellvana_Catalog_Model_Category::i()->load($categoryId);

        $this->assertTrue(is_object($category));

        $products = $category->products();

        $this->assertEquals(1, count($products));
    }

    public function testUrlKey()
    {
        $this->tester->seeNumRecords(2, 'fcom_category');

        $category = Sellvana_Catalog_Model_Category::i()->load(1);
        $categoryChild = $category->createChild("Category 3");

        $this->assertTrue(!empty($categoryChild->url_key), "Not set url_key");
    }

    public function testRenameEntry()
    {
        $category = Sellvana_Catalog_Model_Category::i()->load(2);
        $category->rename("CategoryNew 2");

        $category = Sellvana_Catalog_Model_Category::i()->load(2);
        $this->assertEquals("CategoryNew 2", $category->node_name, "Rename failed");
    }

    public function testMoveEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_category');

        $category = Sellvana_Catalog_Model_Category::i()->load(1);
        $child = $category->createChild("Category 3");

        $this->assertEquals(1, $child->parent_id);

        $child->move(2);
        $this->assertEquals(2, $child->parent_id, "Move failed");
    }

    public function testGetParent()
    {
        $category = Sellvana_Catalog_Model_Category::i()->load(2);
        $parent = $category->parent();
        $this->assertEquals(1, $parent->id(), "Parent not found");
    }

    public function testGetChildren()
    {
        $category = Sellvana_Catalog_Model_Category::i()->load(1);
        $children = $category->children();
        $this->assertEquals(1, count($children), "Children not found");
    }
}
