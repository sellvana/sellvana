<?php defined('BUCKYBALL_ROOT_DIR') || die();

require_once __DIR__ . '/../../../tests/index.php';

class FCom_CustomerGroups_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(static::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit CustomerGroups');
        require_once 'Model/GroupTest.php';
        $suite->addTestSuite('FCom_CustomerGroups_Tests_Model_GroupTest');

        return $suite;
    }
}
