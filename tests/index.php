<?php

//require_once __DIR__.'/lib/PHPUnit/Autoload.php';

require_once dirname(__DIR__).'/FCom/Core/Core.php';

//BDebug::mode(BDebug::MODE_STAGING, false);


BConfig::i()->set('fs/root_dir', dirname(__DIR__));
FCom_Core::i()->init('FCom_Test');
//print_r(BDebug::mode()); exit;
BConfig::i()->add(array(
   'db'=>array(
    'host' => 'localhost',
    'dbname' => 'fulleron_test',
    'username' => 'root',
    'password' => '',),
));
//BApp::set('area', 'FCom_Frontend', true);
BModuleRegistry::i()->bootstrap();

BMigrate::migrateModules();
//FCom_Test_AllTests::suite();