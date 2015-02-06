<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomField_Tests_Model_SetTest
 *
 * @property FCom_CustomField_Model_Set $FCom_CustomField_Model_Set
 * @property FCom_CustomField_Model_SetField $FCom_CustomField_Model_SetField
 */

class FCom_CustomField_Tests_Model_SetTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/SetTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_fieldset'), "Pre-Condition");

        $data = ['id' => 3,
            'set_type' => "product",
            'set_code' => "test3",
            'set_name' => "Test 3"];
        $this->FCom_CustomField_Model_Set->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_fieldset'), "Insert failed");
    }

    public function testAddSetFieldEntry()
    {
        $this->assertEquals(0, $this->getConnection()->getRowCount('fcom_fieldset_field'), "Pre-Condition");

        $data = ['set_id' => 2,
            'field_id' => 1,
            'position' => "10"];

        $this->FCom_CustomField_Model_SetField->create($data)->save();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_fieldset_field'), "Insert failed");
    }
}
