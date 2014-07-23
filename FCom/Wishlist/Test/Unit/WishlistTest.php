<?php
if(!defined('BUCKYBALL_ROOT_DIR')){
    include_once '../../../Test/bootstrap.php';
}

defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Wishlist_Test_Unit_WishlistTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__ . '/WishlistTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_wishlist'), "Pre-Condition");
        $mWislist = FCom_Wishlist_Model_Wishlist::i(true);
        $data = ['customer_id' => 3];
        $mWislist->create($data)->save();
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_wishlist'), "Insert failed");
    }

    public function testAddItem()
    {
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_wishlist_items'), "Pre-Condition");
        $mWislist = FCom_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWislist->load(2);
        $wishlist->addItem(4);

        $this->assertEquals(4, $this->getConnection()->getRowCount('fcom_wishlist_items'), "Insert failed");
    }

    public function testRemoveItem()
    {
        $this->assertEquals(3, $this->getConnection()->getRowCount('fcom_wishlist_items'), "Pre-Condition");
        $mWislist = FCom_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWislist->load(1);
        $this->assertEquals(2, count($wishlist->items()), "Count items before remove");
        $wishlist->removeProduct(1);
        $this->assertEquals(1, count($wishlist->items()), "Count items after remove");

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_wishlist_items'), "Remove item failed");
    }

    public function testClearWishlist()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_wishlist'), "Pre-Condition");
        $mWislist = FCom_Wishlist_Model_Wishlist::i(true);
        $wishlist = $mWislist->load(1);
        $this->assertEquals(2, count($wishlist->items()), "Items count is not correct");

        foreach ($wishlist->items() as $item) {
            $wishlist->removeItem($item);
        }

        $this->assertEquals(0, count($wishlist->items()), "Items count is not correct");
    }

    public function testSessionWishListReturnsValidModel()
    {
        BSession::i()->set('customer_id', 3); // set session user
        /** @var FCom_Wishlist_Model_Wishlist $mWislist */
        $mWislist = FCom_Wishlist_Model_Wishlist::i(true);
        /** @var FCom_Wishlist_Model_Wishlist $sessionWhishlist */
        $sessionWhishlist = $mWislist->sessionWishlist();

        $this->assertNotEmpty($sessionWhishlist->id());
    }
}
