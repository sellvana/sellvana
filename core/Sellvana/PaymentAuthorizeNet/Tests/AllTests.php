<?php defined('BUCKYBALL_ROOT_DIR') || die();

require_once __DIR__ . '/../../../tests/index.php';

/**
 * Class Sellvana_PaymentAuthorizeNet_Tests_AllTests
 *
 * @property Sellvana_PaymentAuthorizeNet_Tests_AllTests $Sellvana_PaymentAuthorizeNet_Tests_AllTests
 */

class Sellvana_PaymentAuthorizeNet_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit AuthorizeNet');
        require_once 'ApiTest.php';
        $suite->addTestSuite('Sellvana_PaymentAuthorizeNet_Tests_ApiTest');

        return $suite;
    }
}
$this->Sellvana_PaymentAuthorizeNet_Tests_AllTests->main();
