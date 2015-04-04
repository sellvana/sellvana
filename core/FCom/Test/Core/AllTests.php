<?php defined('BUCKYBALL_ROOT_DIR') || die();


if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

require_once __DIR__ . '/../../../../tests/index.php';

require_once __DIR__ . '/buckyball/AllTests.php';

class AllTests_Buckyball
{
    public function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * All tests
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Buckyball');

        $suite->addTest(BAllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests_Buckyball::main();
}
