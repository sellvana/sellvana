<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Wishlist_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Wishlist');

        $suite->addTestSuite('FCom_Wishlist_Tests_Model_WishlistTest');


        return $suite;
    }
}
