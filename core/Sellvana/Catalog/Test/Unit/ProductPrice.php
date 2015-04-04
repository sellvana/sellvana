<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class Sellvana_CustomerGroups_Test_Unit_TierPriceTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var Sellvana_Catalog_Model_ProductPrice
     */
    public $model;

    protected function setUp()
    {
        parent::setUp();
        $this->model = Sellvana_Catalog_Model_ProductPrice::i(true);
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
