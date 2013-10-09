<?php

$rootDir = dirname(__DIR__);
require_once $rootDir.'/FCom/Core/Main.php';

$webRoot = BRequest::i()->webRoot(1);
BConfig::i()
    ->set('fs/root_dir', $rootDir)
    ->set('web/base_href', $webRoot)
;

if (file_exists(__DIR__.'/index.local.php')) {
    require_once __DIR__.'/index.local.php';
}

BDebug::mode('DEBUG');
FCom_Core_Main::i()->run('FCom_ApiServer');
