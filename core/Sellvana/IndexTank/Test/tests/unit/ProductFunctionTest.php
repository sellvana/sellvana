<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class ProductTest
 *
 * @property Sellvana_IndexTank_Model_ProductFunction $Sellvana_IndexTank_Model_ProductFunction
 */
class ProductFunctionTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/ProductFunctionTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_function');

        $data = [
            'name'        => "base_price_asc",
            'number'        => "2",
            'definition'       => '-d[0]'
        ];

        Sellvana_IndexTank_Model_ProductFunction::i()->create($data)->save();
        $data = [
            'name'        => "base_price_desc",
            'number'        => "3",
            'definition'       => 'd[0]'
        ];
        Sellvana_IndexTank_Model_ProductFunction::i()->create($data)->save();

        $this->tester->seeNumRecords(4, 'fcom_indextank_product_function');
    }

    public function testListCount()
    {
        $list = Sellvana_IndexTank_Model_ProductFunction::i()->getList();
        $this->assertTrue(is_array($list));
        $this->assertEquals(2, count($list));
    }

    public function testRemoveEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_function');

        $field = Sellvana_IndexTank_Model_ProductFunction::i()->load(1);
        $field->delete();

        $this->tester->seeNumRecords(1, 'fcom_indextank_product_function');
    }

    public function testUpdateEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_indextank_product_function');
        /** @var Sellvana_IndexTank_Model_ProductFunction $func */
        $func = Sellvana_IndexTank_Model_ProductFunction::i()->load(1);
        $this->assertEquals("age", $func->name, "Load failed");
        $func->name = "seconds";
        $func->save();
        $func = Sellvana_IndexTank_Model_ProductFunction::i()->load(1);
        $this->assertEquals("seconds", $func->name, "Update failed");
    }
}
