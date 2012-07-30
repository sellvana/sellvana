<?php

class FCom_Checkout_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Checkout');

        $suite->addTestSuite('FCom_Checkout_Tests_Model_AddressTest');
        
        return $suite;
    }
}
