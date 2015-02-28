<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Customer_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit CustomField');

        $suite->addTestSuite('Sellvana_Customer_Tests_Model_CustomerTest');
        $suite->addTestSuite('Sellvana_Customer_Tests_Model_AddressTest');

        return $suite;
    }
}
