<?php
/**
 * Created by pp
 * @project sellvana_core
 */
$rootDir = dirname(__DIR__);
require_once $rootDir . '/core/FCom/Core/Main.php';
if(empty($argc)){
    BResponse::i()->status(403, null, false);
    exit(1);
}
BConfig::i()->set('fs/root_dir', $rootDir);
FCom_Core_Main::i()->init('FCom_Test');
BModuleRegistry::i()->bootstrap();

/** @var FCom_Test_Admin_Controller_Tests $runner */
$runner = FCom_Test_Admin_Controller_Tests::i();
$tests = $runner->collectTestFiles();
if (!empty($tests)) {
    if($argc > 1){
        $argTests = array_slice($argv, 1);
        $matches = array_intersect($tests, $argTests);
        if($matches){
            $tests = $matches;
        }
    }
    $runner->runTestsText($tests);
}
