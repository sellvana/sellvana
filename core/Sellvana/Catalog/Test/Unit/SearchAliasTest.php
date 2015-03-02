<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Catalog_Test_Unit_SeacrchAliasTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/SearchAliasTest.xml');
    }

    public function testAddEntry()
    {

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_search_alias'), "Pre-Condition");

        $data = ['alias_term' => 'Search alias 3', 'target_term' => 'blank', 'num_hits' => 33];

        Sellvana_Catalog_Model_SearchAlias::i()->create($data)->save();

        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_search_alias'), "Insert failed");
    }

    public function testDeleteEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_search_alias'), "Pre-Condition");

        $searchAlias = Sellvana_Catalog_Model_SearchAlias::i()->load(2);
        $searchAlias->delete();

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_customer'), "Delete failed");
    }
}
