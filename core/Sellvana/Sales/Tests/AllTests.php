<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Sales');

        $suite->addTestSuite('Sellvana_Checkout_Tests_Model_CartTest');
        $suite->addTestSuite('Sellvana_Checkout_Tests_Model_CartAddressTest');
        $suite->addTestSuite('Sellvana_Sales_Tests_Model_OrderTest');


        return $suite;
    }
}
