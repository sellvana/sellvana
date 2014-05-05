<?php

$rootDir = dirname(dirname(__DIR__));
require_once $rootDir . '/FCom/Core/Main.php';

$webRoot = BRequest::i()->webRoot(2);
BConfig::i()
    ->set('fs/root_dir', $rootDir)
    ->set('web/base_href', $webRoot)
;

BDebug::mode('DEVELOPMENT');
FCom_Core_Main::i()->run('FCom_PushServer');
