<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Tests_Index_ProductTest
 *
 * @property Sellvana_IndexTank_Model_ProductField $Sellvana_IndexTank_Model_ProductField
 */
class ProductFieldTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/ProductFieldTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $data = [
            'field_name'        => "description",
            'field_type'        => "text",
            'source_type'       => 'product',
            'source_value'      => "description",
            'search'            => 1
        ];

        Sellvana_IndexTank_Model_ProductField::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_indextank_product_field');
    }

    public function testGetList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = Sellvana_IndexTank_Model_ProductField::i()->getList();
        $this->assertTrue(is_array($list));
        $this->assertEquals(2, count($list));
    }

    public function testGetFacetsList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = Sellvana_IndexTank_Model_ProductField::i()->getFacetsList();
        $this->assertEquals(1, count($list));
    }

    public function testGetSearchList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = Sellvana_IndexTank_Model_ProductField::i()->getSearchList();
        $this->assertEquals(1, count($list));
    }

    public function testGetVariablesList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = Sellvana_IndexTank_Model_ProductField::i()->getVariablesList();
        $this->assertEquals(1, count($list));
    }

    public function testInclusiveList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = Sellvana_IndexTank_Model_ProductField::i()->getInclusiveList();
        $this->assertEquals(1, count($list));
    }

    public function testRemoveEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $field = Sellvana_IndexTank_Model_ProductField::i()->load(1);
        $field->delete();

        $this->tester->seeNumRecords(1, 'fcom_indextank_product_field');
    }

    public function testUpdateEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');
        /** @var Sellvana_IndexTank_Model_ProductField $field */
        $field = Sellvana_IndexTank_Model_ProductField::i()->load(1);
        $this->assertEquals(0, $field->facets, "Load failed");
        $field->facets = 1;
        $field->save();
        $field = Sellvana_IndexTank_Model_ProductField::i()->load(1);
        $this->assertEquals(1, $field->facets, "Update failed");
    }
}
