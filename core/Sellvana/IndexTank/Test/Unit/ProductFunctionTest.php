<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_IndexTank_Test_Unit_ProductFunctionTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/ProductFunctionTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Pre-Condition");

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

        $this->assertEquals(4, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Insert failed");
    }

    public function testListCount()
    {
        $list = Sellvana_IndexTank_Model_ProductFunction::i()->getList();
        $this->assertTrue(is_array($list));
        $this->assertEquals(2, count($list));
    }

    public function testRemoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Pre-Condition");

        $field = Sellvana_IndexTank_Model_ProductFunction::i()->load(1);
        $field->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Remove failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Pre-Condition");

        $func = Sellvana_IndexTank_Model_ProductFunction::i()->load(1);
        $this->assertEquals("age", $func->name, "Load failed");
        $func->name = "seconds";
        $func->save();
        $func = Sellvana_IndexTank_Model_ProductFunction::i()->load(1);
        $this->assertEquals("seconds", $func->name, "Update failed");
    }
}
