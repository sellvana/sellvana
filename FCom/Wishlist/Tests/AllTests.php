<?php

class FCom_Wishlist_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(static::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Wishlist');

        $suite->addTestSuite('FCom_Wishlist_Tests_Model_WishlistTest');


        return $suite;
    }
}
