<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_CustomField_Tests_Model_FieldTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/FieldTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_field'), "Pre-Condition");

        $data = ['id' => 3,
            'field_code' => "FeatureC",
            'field_name' => "Feature C",
            'frontend_label' => "Feature C",
            "table_field_type" => "varchar(255)"];
        $field = $this->FCom_CustomField_Model_Field->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_field'), "Insert failed");

        $this->BDb->ddlClearCache();
        $fieldName = $this->BDb->ddlFieldInfo($field->tableName(), $field->field_code);
        $this->assertTrue(!empty($fieldName), "Column not added");
    }

    public function testDeleteEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_field'), "Pre-Condition");

        $data = ['id' => 3,
            'field_code' => "FeatureC",
            'field_name' => "Feature C",
            'frontend_label' => "Feature C",
            "table_field_type" => "varchar(255)"];
        $field = $this->FCom_CustomField_Model_Field->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_field'), "Insert failed");

        $field->delete();

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_field'), "Delete failed");

        $field2 = $this->FCom_CustomField_Model_Field->load(2);
        $this->BDb->ddlClearCache();
        $fieldName = $this->BDb->ddlFieldInfo($field2->tableName(), $data['field_code']);
        $this->assertTrue(empty($fieldName), "Column not deleted");
    }
}
