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
        $this->Sellvana_IndexTank_Model_ProductField->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_indextank_product_field');
    }

    public function testGetList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = $this->Sellvana_IndexTank_Model_ProductField->getList();
        $this->assertTrue(is_array($list));
        $this->assertEquals(2, count($list));
    }

    public function testGetFacetsList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = $this->Sellvana_IndexTank_Model_ProductField->getFacetsList();
        $this->assertEquals(1, count($list));
    }

    public function testGetSearchList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = $this->Sellvana_IndexTank_Model_ProductField->getSearchList();
        $this->assertEquals(1, count($list));
    }

    public function testGetVariablesList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = $this->Sellvana_IndexTank_Model_ProductField->getVariablesList();
        $this->assertEquals(1, count($list));
    }

    public function testInclusiveList()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $list = $this->Sellvana_IndexTank_Model_ProductField->getInclusiveList();
        $this->assertEquals(1, count($list));
    }

    public function testRemoveEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $field = $this->Sellvana_IndexTank_Model_ProductField->load(1);
        $field->delete();

        $this->tester->seeNumRecords(1, 'fcom_indextank_product_field');
    }

    public function testUpdateEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_field');

        $field = $this->Sellvana_IndexTank_Model_ProductField->load(1);
        $this->assertEquals(0, $field->facets, "Load failed");
        $field->facets = 1;
        $field->save();
        $field = $this->Sellvana_IndexTank_Model_ProductField->load(1);
        $this->assertEquals(1, $field->facets, "Update failed");
    }
}
