<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Test_Unit_TierPriceTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var FCom_Catalog_Model_ProductPrice
     */
    public $model;

    protected function setUp()
    {
        parent::setUp();
        $this->model = FCom_Catalog_Model_ProductPrice::i(true);
    }

    /**
     * @covers FCom_Catalog_Model_ProductPrice::groupsOptions
     */
    public function testGetGroupsOptionsForHtml()
    {
        $product = FCom_Catalog_Model_Product::i()->load(1);

        $productTiers = $this->model->getProductTiers($product);

        $this->assertTrue(is_array($productTiers));
    }
}
