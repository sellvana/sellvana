<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Tests_Model_ProductFunctionTest
 *
 * @property Sellvana_IndexTank_Model_ProductFunction $Sellvana_IndexTank_Model_ProductFunction
 */

class Sellvana_IndexTank_Tests_Model_ProductFunctionTest extends FCom_Test_DatabaseTestCase
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
        $this->Sellvana_IndexTank_Model_ProductFunction->create($data)->save();
        $data = [
            'name'        => "base_price_desc",
            'number'        => "3",
            'definition'       => 'd[0]'
        ];
        $this->Sellvana_IndexTank_Model_ProductFunction->create($data)->save();

        $this->assertEquals(4, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Insert failed");
    }

    public function testListCount()
    {
        $list = $this->Sellvana_IndexTank_Model_ProductFunction->getList();
        $this->assertTrue(is_array($list));
        $this->assertEquals(2, count($list));
    }

    public function testRemoveEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Pre-Condition");

        $field = $this->Sellvana_IndexTank_Model_ProductFunction->load(1);
        $field->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Remove failed");
    }

    public function testUpdateEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_indextank_product_function'), "Pre-Condition");

        $func = $this->Sellvana_IndexTank_Model_ProductFunction->load(1);
        $this->assertEquals("age", $func->name, "Load failed");
        $func->name = "seconds";
        $func->save();
        $func = $this->Sellvana_IndexTank_Model_ProductFunction->load(1);
        $this->assertEquals("seconds", $func->name, "Update failed");
    }
}
