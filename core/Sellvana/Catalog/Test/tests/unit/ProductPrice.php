<?php

/**
 * Created by pp
 * @project fulleron
 */
class TierPriceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sellvana_Catalog_Model_ProductPrice
     */
    public $model;

    /**
     * @var \Sellvana\Wishlist\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->model = Sellvana_Catalog_Model_ProductPrice::i(true);
    }

    protected function _after()
    {
    }

    /**
     * @covers Sellvana_Catalog_Model_ProductPrice::groupsOptions
     */
    public function testGetGroupsOptionsForHtml()
    {
        $product = Sellvana_Catalog_Model_Product::i()->load(1);

        $productTiers = $this->model->getProductPrices($product);

        $this->assertTrue(is_array($productTiers));
    }
}
