<?php
/**
 * Created by pp
 * @project sellvana_core
 */
require_once 'FCom/Core/Main.php';
BConfig::i()->set('fs/root_dir', __DIR__);
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
