<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Admin_Controller_Tests extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/tests/index');
        $this->layout()->view('tests/index')->set('can_cgi', function_exists('exec'));
    }

    public function action_run()
    {

        $path = realpath(dirname(__FILE__) . '/../..');
        $pathBB = FULLERON_ROOT_DIR . '/FCom/buckyball/tests';
        if (function_exists('exec')) {
            $res = exec("phpunit {$path}/AllTests.php", $output);
        } else {
            $output = [$this->_("Cannot run CLI tests from browser.")];
        }

        echo "<h2>FCom tests</h2><br/>";
        echo implode("<br>", $output);
        //echo $res;
        exit;
    }

    public function action_run2()
    {
        require_once 'PHPUnit/Autoload.php';
        //require_once 'PHPUnit/Util/Log/JSON.php';
        //
        //$suite = $this->FCom_Test_AllTests->suite();
        //
        //$listener = new PHPUnit_Util_Log_JSON;
        //$result = new PHPUnit_Framework_TestResult;
        //$result->addListener($listener);
        //
        //ob_start();
        //ini_set('html_errors', 0);
        //$suite->run($result);
        //$results = ob_get_contents();
        //ini_set('html_errors', $html_errors); //TODO: what suppose to be $html_errors?
        //ob_end_clean();
        //$textPrinter = new PHPUnit_TextUI_ResultPrinter;
        //ob_start();
        //ini_set('html_errors', 0);
        //$textPrinter->printResult($result);
        //$results = ob_get_contents();
        //ini_set('html_errors', $html_errors);
        //ob_end_clean();

        $tests = $this->collectTests();
        $results = $this->runTests($tests);

        echo nl2br($results);
        exit;
    }

    public function collectTests()
    {
        $modules = $this->BModuleRegistry->getAllModules();
        $collection = [];
        foreach ($modules as $module) {
            /** @var BModule $module */
            if (!$module || !$module instanceof BModule) {
                continue;
            }
            $rootDir = $module->root_dir;
            $testsDir = $rootDir . '/Test/Unit';
            if (is_dir($testsDir)) {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(realpath($testsDir)),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                while ($it->valid()) {
                    $ext = strtolower(pathinfo($it->key(), PATHINFO_EXTENSION));
                    if (!$it->isDot() && $ext == 'php') {
                        $collection[] = $it->key();
                    }

                    $it->next();
                }
                continue;
            }
        }

        return array_unique($collection);
    }

    public function runTests($tests)
    {
        $suite = new PHPUnit_Framework_TestSuite();

        $original_classes = get_declared_classes();
        foreach ($tests as $test) {
            require_once $test;
        }
        $new_classes = get_declared_classes();
        $tests       = array_diff($new_classes, $original_classes);
        foreach ($tests as $test) {
            if (is_subclass_of($test, 'PHPUnit_Framework_TestCase')) {
                $suite->addTestSuite($test);
            }
        }

        $result = new \PHPUnit_Framework_TestResult();
        $result->addListener(new \PHPUnit_Util_Log_JSON());

        // We need to temporarily turn off html_errors to ensure correct
        // parsing of test debug output
        $html_errors = ini_get('html_errors');
        ini_set('html_errors', 0);

        ob_start();
        $suite->run($result);
        $results = ob_get_contents();
        ob_end_clean();

        ini_set('html_errors', $html_errors);
        return $results;
    }
}
