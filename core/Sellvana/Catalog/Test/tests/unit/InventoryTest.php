<?php

class InventoryTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/InventoryTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $mBin = Sellvana_Catalog_Model_InventoryBin::i(true);
        $data = ['id' => 1, 'title' => 'Stock bin 1', 'description' => 'Stock bin 1 description'];
        $bin = $mBin->create($data)->save();
        $this->tester->seeNumRecords(1, 'fcom_stock_bin');

        $data = ['id' => 1, 'bin_id' => $bin->id(), 'sku' => 'Stock sku 1', 'qty_in_stock' => 11];
        $mSku = Sellvana_Catalog_Model_InventorySku::i(true);
        $mSku->create($data)->save();
        $this->tester->seeNumRecords(1, 'fcom_stock_sku');
    }

}

