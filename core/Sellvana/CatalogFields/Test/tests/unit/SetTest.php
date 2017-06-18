<?php

class SetTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/SetTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_fieldset');

        $data = ['id' => 3,
            'set_type' => "product",
            'set_code' => "test3",
            'set_name' => "Test 3"];
        FCom_Core_Model_Fieldset::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_fieldset');
    }

    public function testAddSetFieldEntry()
    {
        $this->tester->seeNumRecords(0, 'fcom_fieldset_field');

        $data = ['set_id' => 2,
            'field_id' => 1,
            'position' => "10"];

        FCom_Core_Model_FieldsetField::i()->create($data)->save();

        $this->tester->seeNumRecords(1, 'fcom_fieldset_field');
    }
}
