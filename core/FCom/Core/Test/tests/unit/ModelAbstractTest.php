<?php

class ModelAbstractTest extends \Codeception\TestCase\Test
{
    /**
     * @var \FCom\Core\UnitTester
     */
    protected $tester;

    /**
     * @var FCom_Core_View_FormElements $form
     */
    protected $product;

    protected function _before()
    {
        $this->tester->haveInDatabase('fcom_product', [
            'id' => 1, 'product_name' => 'Product 1', 'product_sku' => 'test-1'
        ]);
    }

    public function testSetData()
    {
        /** @var BUtil $util */
        $util = \BUtil::i();

        $this->tester->seeNumRecords(1, 'fcom_product');

        /** @var Sellvana_Catalog_Model_Product $p */
        $p = Sellvana_Catalog_Model_Product::i()->load(1);

        $p->setData('base', '1000')->save(false);
        $this->tester->assertContains('1000', $p->get('data_custom'));
        $this->tester->assertTrue(array_key_exists('base', $p->get('data_custom')), 'Data set is not correct.');

        $p->setData('base', 200, true)->save(false);
        $this->tester->assertTrue(is_array($p->get('data_custom')['base']), 'Data set is not correct');
        $this->tester->assertEquals(200, $p->get('data_custom')['base'][1], 'Data set is not correct');

        $data = [
            'base' => [
                1000, 200
            ],
            'price' => [
                'cost' => 500
            ]
        ];
        $p->setData('price/cost', 500)->save(false);
        $this->tester->assertEquals($data, $p->get('data_custom'));

        $p->setData('price/cost', 1000)->save(false);
        $this->tester->assertEquals(1000, $util->arrayGet($p->get('data_custom'), 'price.cost'), 'Data set is not correct');

        $p->setData('base')->save(false);
        $this->tester->assertNull($p->get('data_custom/base'), 'Data set is not correct');

        $p->setData('price/tier', 100)->save(false);
        $this->tester->assertFalse(is_array($util->arrayGet($p->get('data_custom'), 'price.tier')), 'Data set is not correct');
        $this->tester->assertContains('tier', array_keys($p->get('data_custom')['price']), 'Data set is not correct.');

        $p->setData('price/tier', 200, true)->save(false);
        $this->tester->assertTrue(is_array($util->arrayGet($p->get('data_custom'), 'price.tier')), 'Data set is not correct');
        $this->tester->assertTrue(is_array($util->arrayGet($p->get('data_custom'), 'price.tier')), 'Data set is not correct.');
    }

    public function testGetData()
    {
        $this->tester->seeNumRecords(1, 'fcom_product');

        /** @var Sellvana_Catalog_Model_Product $p */
        $p = Sellvana_Catalog_Model_Product::i()->load(1);
        $data = [
            'base' => [
                1000, 200
            ],
            'price' => [
                'cost' => 500
            ]
        ];
        $p->set('data_custom', $data)->save(false);

        $this->tester->assertEquals(2, count($p->getData('base')), 'Data get is not correct');
        $this->tester->assertContains('cost', array_keys($p->getData('price')), 'Data get is not correct');

        $p->setData('price/test', 'TEST')->save();
        $this->tester->assertTrue(is_array($p->getData()), 'Data get is not correct.');
        $this->tester->assertEquals('TEST', $p->getData('price/test'), 'Data get is not correct');
        $this->tester->assertEquals(2, count($p->getData('price')), 'Data get is not correct');
    }
}