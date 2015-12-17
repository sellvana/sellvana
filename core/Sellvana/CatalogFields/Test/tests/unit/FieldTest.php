<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FieldTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/FieldTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else {
            die('__ERROR__');
        }
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_field');

        $data = [
            'id' => 3,
            'field_code' => "FeatureC",
            'field_name' => "Feature C",
            'frontend_label' => "Feature C",
            "table_field_type" => "varchar(255)"
        ];
        /** @var Sellvana_CatalogFields_Model_Field $field */
        $field = Sellvana_CatalogFields_Model_Field::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_field');

        BDb::i()->ddlClearCache();
        $fieldName = BDb::i()->ddlFieldInfo($field->table(), $field->field_code);
        $this->assertTrue(!empty($fieldName), "Column not added");
    }

    public function testDeleteEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_field');

        $data = [
            'id' => 3,
            'field_code' => "FeatureC",
            'field_name' => "Feature C",
            'frontend_label' => "Feature C",
            "table_field_type" => "varchar(255)"
        ];
        $field = Sellvana_CatalogFields_Model_Field::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_field');

        $field->delete();

        $this->tester->seeNumRecords(2, 'fcom_field');

        $field2 = Sellvana_CatalogFields_Model_Field::i()->load(2);
        BDb::i()->ddlClearCache();
        $fieldName = BDb::i()->ddlFieldInfo($field2->table(), $data['field_code']);
        $this->assertTrue(empty($fieldName), "Column not deleted");
    }
}
