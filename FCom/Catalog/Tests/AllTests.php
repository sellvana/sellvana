<?php

class FCom_Catalog_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Catalog');

        $suite->addTestSuite('FCom_Catalog_Tests_Model_ProductTest');
        $suite->addTestSuite('FCom_Catalog_Tests_Model_CategoryProductTest');
        $suite->addTestSuite('FCom_Catalog_Tests_Model_CategoryTest');

        return $suite;
    }
}
