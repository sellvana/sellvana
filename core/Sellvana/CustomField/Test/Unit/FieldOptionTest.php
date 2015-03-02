<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_CustomField_Test_Unit_FieldOptionTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/FieldOptionTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_field_option'), "Pre-Condition");

        $data = ['id' => 3, 'field_id' => "1", 'label' => "Feature A2"];
        Sellvana_CustomField_Model_FieldOption::i()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_field_option'), "Insert failed");
    }

    public function testDeleteEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_field_option'), "Pre-Condition");

        $fieldOption = Sellvana_CustomField_Model_FieldOption::i()->load(2);
        $fieldOption->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_field_option'), "Delete failed");
    }
}
