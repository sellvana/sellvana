<?php

class WishlistTest extends \Codeception\TestCase\Test
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
        $xml = simplexml_load_file(__DIR__ . '/WishlistTest.xml');
        if ($xml) {
            foreach ($xml->children() as $table => $field) {
                $this->tester->haveInDatabase((string)$table, (array)BUtil::i()->arrayFromXml($field)['@attributes']);
            }
        } else die('__ERROR__');
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_wishlist');
        $mWishlist = Sellvana_Wishlist_Model_Wishlist::i(true);
        $data = ['customer_id' => 3, 'title' => 'test'];
        $mWishlist->create($data)->save();
        $this->tester->seeNumRecords(3, 'fcom_wishlist');
        $this->tester->seeInDatabase('fcom_wishlist', ['customer_id' => 3, 'title' => 'test']);
    }

    public function testAddItem()
    {
        $this->tester->seeNumRecords(3, 'fcom_wishlist_items');
        /** @var Sellvana_Wishlist_Model_Wishlist $mWishlist */
        $mWishlist = Sellvana_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWishlist->load(1);
        $wishlist->addItem(4);

        $this->tester->seeNumRecords(4, 'fcom_wishlist_items');
        $this->tester->seeInDatabase('fcom_wishlist_items', ['product_id' => 4]);
    }

    public function testRemoveItem()
    {
        $this->tester->seeNumRecords(3, 'fcom_wishlist_items');
        /** @var Sellvana_Wishlist_Model_Wishlist $mWishlist */
        $mWishlist = Sellvana_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWishlist->load(1);
        $this->assertEquals(2, count($wishlist->items()), "Count items before remove");
        $wishlist->removeProduct(1);
        $this->assertEquals(1, count($wishlist->items()), "Count items after remove");

        $this->tester->seeNumRecords(2, 'fcom_wishlist_items');
    }

    public function testClearWishlist()
    {
        $this->tester->seeNumRecords(2, 'fcom_wishlist');
        /** @var Sellvana_Wishlist_Model_Wishlist $mWishlist */
        $mWishlist = Sellvana_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWishlist->load(1);
        $this->assertEquals(2, count($wishlist->items()), "Items count is not correct");

        foreach ($wishlist->items() as $item) {
            $wishlist->removeItem($item);
        }

        $this->assertEquals(0, count($wishlist->items()), "Items count is not correct");
    }

    public function testSessionWishListReturnsValidModel()
    {
        BSession::i()->set('customer_id', 3); // set session user
        /** @var Sellvana_Wishlist_Model_Wishlist $mWishlist */
        $mWishlist = Sellvana_Wishlist_Model_Wishlist::i(true);
        /** @var Sellvana_Wishlist_Model_Wishlist $sessionWhishlist */
        $sessionWishlist = $mWishlist->sessionWishlist(true);

        $this->assertNotEmpty($sessionWishlist->id());
        BSession::i()->set('customer_id', null); // set session user
    }
}
