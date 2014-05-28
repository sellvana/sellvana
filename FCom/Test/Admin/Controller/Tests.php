<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Test_Admin_Controller_Tests extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/tests/index');
    }

    public function action_run()
    {

        $path = realpath(dirname(__FILE__) . '/../..');
        $pathBB = FULLERON_ROOT_DIR . '/FCom/buckyball/tests';

        $res = exec("phpunit {$path}/AllTests.php", $output);

        echo "<h2>FCom tests</h2><br/>";
        echo implode("<br>", $output);
        //echo $res;
        exit;
    }

    public function action_run2()
    {
        require_once 'PHPUnit/Autoload.php';
        require_once 'PHPUnit/Util/Log/JSON.php';

        $suite = FCom_Test_AllTests::i()->suite();

        $listener = new PHPUnit_Util_Log_JSON;
        $result = new PHPUnit_Framework_TestResult;
        $result->addListener($listener);

        ob_start();
        ini_set('html_errors', 0);
        $suite->run($result);
        $results = ob_get_contents();
        ini_set('html_errors', $html_errors); //TODO: what suppose to be $html_errors?
        ob_end_clean();
        $textPrinter = new PHPUnit_TextUI_ResultPrinter;
        ob_start();
        ini_set('html_errors', 0);
        $textPrinter->printResult($result);
        $results = ob_get_contents();
        ini_set('html_errors', $html_errors);
        ob_end_clean();
        echo nl2br($results);
        exit;
    }
}
