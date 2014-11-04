<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Catalog_Test_Unit_InventoryTest extends FCom_Test_DatabaseTestCase
{

    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/StockTest.xml');
    }

    public function testAddEntry()
    {
        $mBin = FCom_Catalog_Model_InventoryBin::i(true);
        $data = ['id' => 1, 'title' => 'Stock bin 1', 'description' => 'Stock bin 1 description'];
        $bin = $mBin->create($data)->save();
        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_stock_bin'), "Inserting bin failed");

        $data = ['id' => 1, 'bin_id' => $bin->id(), 'sku' => 'Stock sku 1', 'qty_in_stock' => 11];
        $mSku = FCom_Catalog_Model_InventorySku::i(true);
        $mSku->create($data)->save();
        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_stock_sku'), "Inserting sku failed");
    }

}

