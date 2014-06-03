<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit CustomField');

        $suite->addTestSuite('FCom_Customer_Tests_Model_CustomerTest');
        $suite->addTestSuite('FCom_Customer_Tests_Model_AddressTest');

        return $suite;
    }
}
