<?php

class FieldOptionTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/FieldOptionTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_field_option');

        $data = ['id' => 3, 'field_id' => "1", 'label' => "Feature A2"];
        FCom_Core_Model_FieldOption::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_field_option');
    }

    public function testDeleteEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_field_option');

        $fieldOption = FCom_Core_Model_FieldOption::i()->load(2);
        $fieldOption->delete();

        $this->tester->seeNumRecords(1, 'fcom_field_option');
    }
}
