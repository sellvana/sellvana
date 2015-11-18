<?php

namespace Wishlist;

use Wishlist\Helper\Unit;

class WishlistTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Wishlist\UnitTester
     */
    protected $tester;

    /**
     * @var \Wishlist\Helper\Unit
     */
    protected $helper;

    function xml2array ( $xmlObject, $out = array () )
    {
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;

        return $out;
    }

    protected function _before()
    {
        $xml = simplexml_load_file(__DIR__ . '/WishlistTest.xml');
        foreach ($xml->children() as $table => $field) {
            $this->tester->haveInDatabase((string)$table, (array)$this->xml2array($field)['@attributes']);
        }
    }

    protected function _after()
    {
    }

    public function testAddEntry()
    {
        $this->tester->seeNumRecords(2, 'fcom_wishlist');
        $mWislist = \Sellvana_Wishlist_Model_Wishlist::i(true);
        $data = ['customer_id' => 3, 'title' => 'test'];
        $mWislist->create($data)->save();
        $this->tester->seeInDatabase('fcom_wishlist', ['customer_id' => 3, 'title' => 'test']);

        $this->tester->seeNumRecords(3, 'fcom_wishlist');
    }

    public function testAddItem()
    {
        $this->tester->seeNumRecords(3, 'fcom_wishlist_items');
        $mWislist = \Sellvana_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWislist->load(2);
        $wishlist->addItem(4);
        $this->tester->seeInDatabase('fcom_wishlist_items', ['product_id' => 4]);

        $this->tester->seeNumRecords(4, 'fcom_wishlist_items');
    }

    public function testRemoveItem()
    {
        $this->tester->seeNumRecords(3, 'fcom_wishlist_items');
        $mWislist = \Sellvana_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWislist->load(1);
        $this->assertEquals(2, count($wishlist->items()), "Count items before remove");
        $wishlist->removeProduct(1);
        $this->assertEquals(1, count($wishlist->items()), "Count items after remove");
        $this->tester->seeNumRecords(2, 'fcom_wishlist_items');
    }

    public function testClearWishlist()
    {
        $this->tester->seeNumRecords(2, 'fcom_wishlist');
        $mWislist = \Sellvana_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWislist->load(1);
        $this->assertEquals(2, count($wishlist->items()), "Items count is not correct");

        foreach ($wishlist->items() as $item) {
            $wishlist->removeItem($item);
        }

        $this->assertEquals(0, count($wishlist->items()), "Items count is not correct");
    }

    public function testSessionWishListReturnsValidModel()
    {
        \BSession::i()->set('customer_id', 3); // set session user
        /** @var Sellvana_Wishlist_Model_Wishlist $mWislist */
        $mWislist = \Sellvana_Wishlist_Model_Wishlist::i(true);
        /** @var Sellvana_Wishlist_Model_Wishlist $sessionWhishlist */
        $sessionWhishlist = $mWislist->sessionWishlist(true);

        $this->assertNotEmpty($sessionWhishlist->id());
        \BSession::i()->set('customer_id', null); // set session user
    }
}
