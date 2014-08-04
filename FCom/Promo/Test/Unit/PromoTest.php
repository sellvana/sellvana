<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Promo_Test_Unit_PromoTest extends FCom_Test_DatabaseTestCase
{

    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/PromoTest.xml');
    }

    public function testAddEntry()
    {

    }

    public function testOnPromoCartValidate()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Pre-Condition");

        $cart = FCom_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(3, ['qty' => 2, 'price' => 5]);
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_sales_cart'), "Update cart failed");
        $this->assertEquals(3, count($cart->items()), "Items count is not correct");
        $this->assertEquals(7, $cart->itemQty(), "Items count is not correct");

    }
}
 