<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_CatalogFields_Test_Unit_FieldTest extends FCom_Test_DatabaseTestCase
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
        $field = Sellvana_CatalogFields_Model_Field::i()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_field'), "Insert failed");

        BDb::i()->ddlClearCache();
        $fieldName = BDb::i()->ddlFieldInfo($field->tableName(), $field->field_code);
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
        $field = Sellvana_CatalogFields_Model_Field::i()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_field'), "Insert failed");

        $field->delete();

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_field'), "Delete failed");

        $field2 = Sellvana_CatalogFields_Model_Field::i()->load(2);
        BDb::i()->ddlClearCache();
        $fieldName = BDb::i()->ddlFieldInfo($field2->tableName(), $data['field_code']);
        $this->assertTrue(empty($fieldName), "Column not deleted");
    }
}
