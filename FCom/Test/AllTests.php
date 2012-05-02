<?php

require_once __DIR__ . '/Initialize.php';

/**
* All Fulleron Tests
*
* This test suite will find all Fulleron modules that have test suites
* named *AllTests.php and will add it to this suite to be executed.
* Individual module suites can be run directly with the phpunit command.
*
*/
class FCom_Tests_AllTests {

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('All Fulleron Tests');

        $path = realpath(dirname(__FILE__) . '/..') . '/*/tests/*AllTests.php';

        $moduleSuitePaths = glob($path);
        //print_r($moduleSuitePaths);exit;
        foreach ($moduleSuitePaths as $suitePath) {
            require_once $suitePath;
            // Separate out the component parts of the AllTests code file.
            $class_name = self::get_class_from_path($suitePath);
            $suite->addTest(call_user_func(array($class_name, 'suite')));
        }
        return $suite;
    }

  public static function get_class_from_path($filename)
  {
      $path = realpath(dirname(__FILE__) . '/..');

      $newpath = substr($filename, strlen($path));
      $class_name = 'FCom'.str_replace("/", "_", $newpath);
      $class_name = str_replace(".php", "", $class_name);
      return $class_name;
  }
}
