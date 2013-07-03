<?php

$rootDir = dirname(dirname(__DIR__));
require_once $rootDir.'/FCom/Core/Main.php';

$webRoot = BRequest::i()->webRoot(2);
BConfig::i()->add(array(
    'fs' => array(
        'root_dir' => $rootDir,
    ),
    'web' => array(
        'base_href' => $webRoot,
    ),
));

BDebug::mode('DEVELOPMENT');
FCom_Core_Main::i()->run('FCom_Comet');
