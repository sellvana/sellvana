<?php
if ( !defined( 'PHPUnit_MAIN_METHOD' ) ) {
    define( 'PHPUnit_MAIN_METHOD', 'FCom_Test_AllTests::suite' );
}
/**
* All Fulleron Tests
*
* This test suite will find all Fulleron modules that have test suites
* named *AllTests.php and will add it to this suite to be executed.
* Individual module suites can be run directly with the phpunit command.
*
*/
class FCom_Test_AllTests extends BClass
{

    public static function suite()
    {
        $sapi_type = php_sapi_name();
        if ( substr( $sapi_type, 0, 3 ) == 'cgi' || substr( $sapi_type, 0, 3 ) == 'cli' ) {
            require_once dirname( dirname( __DIR__ ) ) . '/tests/index.php';
        }
        $suite = new PHPUnit_Framework_TestSuite( 'All Fulleron Tests' );

        $modules = BModuleRegistry::i()->getAllModules();

        $testModules = array();
        foreach ( $modules as $module ) {
            if ( ( isset( $module->auto[ 'all' ] ) || isset( $module->auto[ 'tests' ] ) ) ) { //TODO: move to tests
                $module->tests = $module->name . '_Tests_AllTests';
            }
            if ( !empty( $module->tests ) && class_exists( $module->tests ) ) {
                $testModules[] = $module;
                //print_R($module->tests);
                $suite->addTestSuite( call_user_func( array( $module->tests, 'suite' ) ) );
            }
        }

        require_once FULLERON_ROOT_DIR . '/FCom/Test/Core/buckyball/AllTests.php';
        $suite->addTest( BAllTests::suite() );

        return $suite;
    }
}

if ( PHPUnit_MAIN_METHOD == 'FCom_Test_AllTests::suite' ) {
    FCom_Test_AllTests::i()->suite();
}
