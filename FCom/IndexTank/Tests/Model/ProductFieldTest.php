<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Tests_Model_ProductFieldTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/ProductFieldTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $data = [
            'field_name'        => "description",
            'field_type'        => "text",
            'source_type'       => 'product',
            'source_value'      => "description",
            'search'            => 1
        ];
        $this->FCom_IndexTank_Model_ProductField->orm()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Insert failed");
    }

    public function testGetList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = $this->FCom_IndexTank_Model_ProductField->getList();
        $this->assertTrue(is_array($list));
        $this->assertEquals(2, count($list));
    }

    public function testGetFacetsList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = $this->FCom_IndexTank_Model_ProductField->getFacetsList();
        $this->assertEquals(1, count($list));
    }

    public function testGetSearchList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = $this->FCom_IndexTank_Model_ProductField->getSearchList();
        $this->assertEquals(1, count($list));
    }

    public function testGetVariablesList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = $this->FCom_IndexTank_Model_ProductField->getVariablesList();
        $this->assertEquals(1, count($list));
    }

    public function testInclusiveList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = $this->FCom_IndexTank_Model_ProductField->getInclusiveList();
        $this->assertEquals(1, count($list));
    }

    public function testRemoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $field = $this->FCom_IndexTank_Model_ProductField->load(1);
        $field->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Remove failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $field = $this->FCom_IndexTank_Model_ProductField->load(1);
        $this->assertEquals(0, $field->facets, "Load failed");
        $field->facets = 1;
        $field->save();
        $field = $this->FCom_IndexTank_Model_ProductField->load(1);
        $this->assertEquals(1, $field->facets, "Update failed");
    }
}
