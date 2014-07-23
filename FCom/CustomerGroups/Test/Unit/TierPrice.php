<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @project fulleron
 */

class FCom_CustomerGroups_Test_Unit_TierPriceTest
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var FCom_CustomerGroups_Model_TierPrice
     */
    public $model;

    protected function setUp()
    {
        parent::setUp();
        $this->model = FCom_CustomerGroups_Model_TierPrice::i(true);
    }

    /**
     * @covers FCom_CustomerGroups_Model_TierPrice::groupsOptions
     */
    public function testGetGroupsOptionsForHtml()
    {
        $product = FCom_Catalog_Model_Product::i()->load(1);

        $productTiers = FCom_CustomerGroups_Model_TierPrice::i()->getProductTiers($product);

        $this->assertTrue(is_array($productTiers));
    }
}
