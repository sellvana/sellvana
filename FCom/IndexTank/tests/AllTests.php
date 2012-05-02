<?php
require_once realpath(dirname(__FILE__).'/../..') . '/Test/Initialize.php';
require_once realpath(dirname(__FILE__)) . '/Index/ProductTest.php';
require_once realpath(dirname(__FILE__)) . '/Model/ProductFieldTest.php';
require_once realpath(dirname(__FILE__)) . '/Model/ProductFunctionTest.php';

class FCom_IndexTank_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit IndexTank');
        $suite->addTestSuite('FCom_IndexTank_Index_Tests_ProductTest');
        $suite->addTestSuite('FCom_IndexTank_Model_Tests_ProductFieldTest');
        $suite->addTestSuite('FCom_IndexTank_Model_Tests_ProductFunctionTest');
        return $suite;
    }
}
