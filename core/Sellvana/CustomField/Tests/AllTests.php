<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_CustomField_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit CustomField');

        $suite->addTestSuite('Sellvana_CustomField_Tests_Model_FieldTest');
        $suite->addTestSuite('Sellvana_CustomField_Tests_Model_FieldOptionTest');
        $suite->addTestSuite('Sellvana_CustomField_Tests_Model_SetTest');

        return $suite;
    }
}
