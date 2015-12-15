<?php defined('BUCKYBALL_ROOT_DIR') || die();

class PromoTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/PromoTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else {
            die('__ERROR__');
        }
    }

    public function testAddEntry()
    {

    }

    public function testOnPromoCartValidate()
    {
        $this->tester->seeNumRecords(2, 'fcom_sales_cart');

        /** @var Sellvana_Sales_Model_Cart $cart */
        $cart = Sellvana_Sales_Model_Cart::i()->load(1);
        $this->assertEquals(2, count($cart->items()), "Items count is not correct");
        $this->assertEquals(5, $cart->itemQty(), "Items count is not correct");

        $cart->addProduct(3, ['qty' => 2, 'price' => 5]);
        $items = Sellvana_Sales_Model_Cart_Item::i()->orm()->where('cart_id', 1)->find_many_assoc();
        $this->assertEquals(3, count($items), "Items count is not correct");
        $this->assertEquals(7, $cart->itemQty(), "Items count is not correct");

        $this->tester->seeNumRecords(2, 'fcom_sales_cart');
    }
}
 