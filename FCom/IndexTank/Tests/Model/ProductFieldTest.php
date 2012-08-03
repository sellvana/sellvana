<?php
class FCom_IndexTank_Tests_Model_ProductFieldTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/ProductFieldTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $data = array(
            'field_name'        => "description",
            'field_type'        => "text",
            'source_type'       => 'product',
            'source_value'      => "description",
            'search'            => 1
        );
        FCom_IndexTank_Model_ProductField::orm()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Insert failed");
    }

    public function testGetList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = FCom_IndexTank_Model_ProductField::i()->getList();
        $this->assertTrue(is_array($list));
        $this->assertEquals(2, count($list));
    }

    public function testGetFacetsList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = FCom_IndexTank_Model_ProductField::i()->getFacetsList();
        $this->assertEquals(1, count($list));
    }

    public function testGetSearchList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = FCom_IndexTank_Model_ProductField::i()->getSearchList();
        $this->assertEquals(1, count($list));
    }

    public function testGetVariablesList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = FCom_IndexTank_Model_ProductField::i()->getVarialbesList();
        $this->assertEquals(1, count($list));
    }

    public function testInclusiveList()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $list = FCom_IndexTank_Model_ProductField::i()->getInclusiveList();
        $this->assertEquals(1, count($list));
    }

    public function testRemoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $field = FCom_IndexTank_Model_ProductField::load(1);
        $field->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Remove failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_field'), "Pre-Condition");

        $field = FCom_IndexTank_Model_ProductField::load(1);
        $this->assertEquals(0, $field->facets, "Load failed");
        $field->facets=1;
        $field->save();
        $field = FCom_IndexTank_Model_ProductField::load(1);
        $this->assertEquals(1, $field->facets, "Update failed");
    }
}