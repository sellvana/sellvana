<?php defined('BUCKYBALL_ROOT_DIR') || die();

class CategoryProductTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/CategoryProductTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_product');
        $this->tester->seeNumRecords(1, 'fcom_category');
        $this->tester->seeNumRecords(1, 'fcom_category_product');

        $productId = 2;
        $categoryId = 1;
        Sellvana_Catalog_Model_CategoryProduct::i()->create(['product_id' => $productId, 'category_id' => $categoryId])->save();

        $this->tester->seeNumRecords(2, 'fcom_category_product');
    }
}
