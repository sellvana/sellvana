<?php

require_once __DIR__ . '/../../../tests/index.php';

class FCom_AuthorizeNet_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit AuthorizeNet');
        require_once 'ApiTest.php';
        $suite->addTestSuite('FCom_AuthorizeNet_Tests_ApiTest');

        return $suite;
    }
}
FCom_AuthorizeNet_Tests_AllTests::main();