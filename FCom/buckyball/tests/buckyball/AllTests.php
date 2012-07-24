<?php

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'BAllTests::main');
}

require_once __DIR__.'/com/BUtilTest.php';
require_once __DIR__.'/com/BLocaleTest.php';
require_once __DIR__.'/com/BAppTest.php';
require_once __DIR__.'/com/BConfigTest.php';
require_once __DIR__.'/com/BClassRegistryTest.php';

class BAllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * All tests
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Buckyball - Buckyball');

        // Start tests...
        $suite->addTestSuite('BUtil_Test');
        $suite->addTestSuite('BLocale_Test');
        $suite->addTestSuite('BApp_Test');
        $suite->addTestSuite('BConfig_Test');
        $suite->addTestSuite('BClassRegistry_Test');


        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'BAllTests::main') {
    BAllTests::main();
}