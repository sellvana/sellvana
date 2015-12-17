<?php defined('BUCKYBALL_ROOT_DIR') || die();

class SearchHistoryTest extends FCom_Test_DatabaseTestCase
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
        $xml = simplexml_load_file(__DIR__ . '/SearchHistoryTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_search_history');

        $data = ['query' => 'Search history 3', 'num_searches' => 3, 'num_products_found_last' => 33];

        Sellvana_Catalog_Model_SearchHistory::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_search_history');
    }

    public function testDeleteEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_search_history');

        $searchHistory = Sellvana_Catalog_Model_SearchHistory::i()->load(2);
        $searchHistory->delete();

        $this->tester->seeNumRecords(1, 'fcom_search_history');
    }
}
