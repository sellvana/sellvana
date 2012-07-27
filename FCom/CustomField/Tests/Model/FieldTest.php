<?php

class FCom_CustomField_Tests_Model_FieldTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/FieldTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_field'), "Pre-Condition");

        $data = array('id' => 3,
            'field_code' => "FeatureC",
            'field_name' => "Feature C",
            'frontend_label' => "Feature C",
            "table_field_type" => "varchar(255)");
        FCom_CustomField_Model_Field::create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_field'), "Insert failed");
    }
}