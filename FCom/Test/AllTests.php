<?php
require_once '../../tests/index.php';

/**
* All Fulleron Tests
*
* This test suite will find all Fulleron modules that have test suites
* named *AllTests.php and will add it to this suite to be executed.
* Individual module suites can be run directly with the phpunit command.
*
*/
class FCom_Test_AllTests {

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('All Fulleron Tests');

        $modules = BModuleRegistry::i()->debug();

        foreach ($modules as $module) {
            if(!empty($module->tests) && class_exists($module->tests)){
                $suite->addTest(call_user_func(array($module->tests, 'suite')));
            }
        }
        return $suite;
    }
}
