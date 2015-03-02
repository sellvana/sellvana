<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Catalog_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Catalog');

        $suite->addTestSuite('Sellvana_Catalog_Tests_Model_ProductTest');
        $suite->addTestSuite('Sellvana_Catalog_Tests_Model_CategoryProductTest');
        $suite->addTestSuite('Sellvana_Catalog_Tests_Model_CategoryTest');

        return $suite;
    }
}
