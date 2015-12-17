<?php defined('BUCKYBALL_ROOT_DIR') || die();

class SeacrchAliasTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/SearchAliasTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_search_alias');

        $data = ['alias_term' => 'Search alias 3', 'target_term' => 'blank', 'num_hits' => 33];

        Sellvana_Catalog_Model_SearchAlias::i()->create($data)->save();

        $this->tester->seeNumRecords(3, 'fcom_search_alias');
    }

    public function testDeleteEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_search_alias');

        $searchAlias = Sellvana_Catalog_Model_SearchAlias::i()->load(2);
        $searchAlias->delete();

        $this->tester->seeNumRecords(2, 'fcom_customer');
    }
}
