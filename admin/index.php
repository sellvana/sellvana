<?php

$rootDir = dirname(__DIR__);
require $rootDir.'/FCom/FCom.php';

$r = BRequest::i();

BConfig::i()->add(array(
    'root_dir' => $rootDir,
    'web' => array('base_store' => BRequest::i()->webRoot(1)),
    'modules' => array(
        'Denteva_Admin' => array('run_level'=>BModule::REQUIRED),
        'Denteva_Merge' => array('run_level'=>BModule::REQUIRED),
    )
));

BDebug::backtraceOn('MODULE UPDATE: FCom_Catalog');

FCom::i()->run('FCom_Admin');