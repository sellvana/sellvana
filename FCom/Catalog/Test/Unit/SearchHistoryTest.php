<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Test_Unit_SearchHistoryTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/SearchHistoryTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_search_history'), "Pre-Condition");

        $data = ['query' => 'Search history 3', 'num_searches' => 3, 'num_products_found_last' => 33];

        FCom_Catalog_Model_SearchHistory::i()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_search_history'), "Insert failed");
    }

    public function testDeleteEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_search_history'), "Pre-Condition");

        $searchHistory = FCom_Catalog_Model_SearchHistory::i()->load(2);
        $searchHistory->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_search_history'), "Delete failed");
    }
}
